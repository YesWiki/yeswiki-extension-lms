<?php
namespace YesWiki\Lms\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\PageManager;
use YesWiki\Lms\Service\ImportManager;
use YesWiki\Wiki;

class ImportCoursesCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'lms:import-courses';

    protected $wiki;
    protected $remote_url;
    protected $remote_token;
    protected $upload_path;
    protected $force;
    protected $keep_original;
    protected $last_choice;
    protected $peertube_token;

    protected $courses;
    protected $modules;
    protected $activities;

    public function __construct(Wiki &$wiki)
    {
        parent::__construct();
        $this->wiki = $wiki;
        $this->peertube_token = null;
        $this->importManager = $this->wiki->services->get(ImportManager::class);
    }

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Import courses from another YesWiki url.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to import courses, and related modules and activities from another YesWiki with LMS extension.')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'URL to another wiki you wish to copy')
            ->addArgument(
                'token',
                InputArgument::OPTIONAL,
                'API token for that wiki, found in `wakka.config.php`. Not needed for public APIs',
                'public')
            ->addOption(
                'force', 'f',
                InputOption::VALUE_NONE,
                'Will force updating of existing courses, modules and activities')
            ->addOption(
                'course', 'c',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Which courses to import')
            ->addOption(
                'peertube-url', null,
                InputOption::VALUE_REQUIRED,
                'From which peertube instance url to import from')
        ;
    }


    private function fetch_api($api_args, $log_name, OutputInterface $output)
    {
      $output->writeln('<info>Fetching '.$log_name.'</>');

      try {
        $data = $this->importManager->fetchEntriesFromApi($this->remote_url, $this->remote_token, $api_args);
      } catch (\Exception $e) {
        $output->writeln('<error>Error: '.$e->getMessage().'</>');
      }

      return $data;
    }

    private function importToPeertube($url, $title, OutputInterface $output)
    {
        $output->writeln('<info>Importing video '.$title.' ('.$url.')</>');
        $this->importManager->importToPeertube($url, $title);
    }

    private function cURLDownload($from, $to, $force, OutputInterface $output)
    {
        try {
          $out = $this->importManager->cURLDownload($from, $to, $force);
        } catch (\Exception $e) {
          $output->writeln('<error>'.$e->getMessage().'</>');
          return;
        }
        $output->writeln('<info>'.$out.'</>');
    }

    private function downloadAttachments(&$bazarPage, OutputInterface $output)
    {
        // Handle Pictures and file attachments
        $force = $this->last_choice == 'r' || $this->last_choice == 'o';
        // Downloading images
        $images = $this->importManager->findImages($this->remote_url, $bazarPage);

        if ($c = count($images)) {
            $output->writeln(
                '<info>Downloading '.$c.' image'.(($c>1)?'s':'').' for '.$bazarPage['id_fiche'].'</>'
            );
            foreach ($images as $image) {
                $this->importManager->downloadImage($this->remote_url, $image, $force);
            }
        }

        // Downloading other attachments
        $attachments = $this->importManager->findFileAttachments($this->remote_url, $bazarPage);

        if ($c = count($attachments)) {
            $output->writeln(
                '<info>Downloading '.$c.' attachment'.(($c>1)?'s':'').' for '.$bazarPage['id_fiche'].'</>'
            );

            foreach ($attachments as $attachment) {
                $this->importManager->downloadAttachment($this->remote_url, $bazarPage['id_fiche'], $bazarPage['date_maj_fiche'], $attachment, $force);
            }
        }

        $wiki_regex = '#url="' . preg_quote($this->remote_url, '#')
                    . '(\?.+/download&(?:amp;)?file=(.*))"#Ui';
        $replaced = preg_replace(
            $wiki_regex,
            'url="'.$this->wiki->getBaseUrl().'/$1"',
            (!empty($bazarPage['bf_contenu']) ? $bazarPage['bf_contenu'] : $bazarPage['bf_description'] ?? ''),
        );
        if (!empty($bazarPage['bf_contenu'])) {
            $bazarPage['bf_contenu'] = $replaced;
        } else {
            $bazarPage['bf_description'] = $replaced;
        }

      // Handle Videos if a peertube location is configured
      if (!empty($this->peertube_token)) {
        try {
          $videos = $this->importManager->findVideos(
              !empty($bazarPage['bf_contenu']) ?
              $bazarPage['bf_contenu'] : $bazarPage['bf_description'] ?? ''
          );
        } catch (\Exception $e) {
          $output->writeln('<error>'.$e->getMessage().'</>');
          die(1);
        }

        foreach ($videos as $video) {
          $this->importToPeertube($video['url'], $video['title'], $output);
        }
      }
    }

    private function askWhenDuplicate($localEntry, $remoteEntry, InputInterface $input, OutputInterface $output)
    {
        if ($this->keep_original) {
            return false;
        }

        $questionHelper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Would you like to',
            [
          'l' => 'Keep local entry '.$this->wiki->href('', $localEntry['id_fiche']). ' last edited at ' . $localEntry['date_maj_fiche'],
          'r' => 'Overwrite with remote entry '.$this->remote_url . '?' . $remoteEntry['id_fiche'] . ' last edited at ' . $remoteEntry['date_maj_fiche'],
          'k' => 'Always keep original',
          'o' => 'Always overwrite'
        ],
            'l'
        );
        $this->last_choice = $questionHelper->ask($input, $output, $question);
        switch ($this->last_choice) {
        case 'o':
          $this->force = true;
          // no break
        case 'r':
          return true;
          break;
        case 'k':
          $this->keep_original = true;
          // no break
        case 'l':
          return false;
          break;
      }
    }

    protected function importActivity($activityId, $activityData, InputInterface $input, OutputInterface $output)
    {
      $entryManager = $this->wiki->services->get(EntryManager::class);
      if (is_null($localActivity = $entryManager->getOne($activityId))) {
          $output->writeln('<info>Importing activity "' . $activityId . '"</>');
          $createActivity = true;
      } elseif ($this->force || $this->askWhenDuplicate($localActivity, $activityData, $input, $output)) {
          $output->writeln('<comment>Activity "' . $activityId . '" already exists, updating it</>');
          $createActivity = false;
      } else {
          $output->writeln('<comment>Activity "' . $activityId . '" already exists, not importing</>');
          return;
      }

      // Import activity here
      $this->downloadAttachments($activityData, $output, $input->getOption('peertube-url'));

      $activityData['antispam'] = 1;
      if ($createActivity) {
          $entryManager->create(1201, $activityData);
      } else {
          $this->wiki->SavePage(
              $activityId,
              json_encode($activityData),
              '',
              true
          );
      }
    }

    protected function importModule($moduleId, $moduleData, InputInterface $input, OutputInterface $output)
    {
      $entryManager = $this->wiki->services->get(EntryManager::class);
      if (is_null($localModule = $entryManager->getOne($moduleId))) {
          $output->writeln('<info>Importing module "' . $moduleId . '"</>');
          $createModule = true;
      } elseif ($this->force || $this->askWhenDuplicate($localModule, $moduleData, $input, $output)) {
          $output->writeln('<comment>Module "' . $moduleId . '" already exists, updating it</>');
          $createModule = false;
      } else {
          $output->writeln('<comment>Module "' . $moduleId . '" already exists, not importing</>');
          return;
      }

      $this->downloadAttachments($moduleData, $output, $input->getOption('peertube-url'));

      $module_activities = explode(',', $moduleData['checkboxfiche1201bf_activites']);


      foreach ($module_activities as $module_activity) {
          $activity = $this->activities[$module_activity];

          $this->importActivity($module_activity, $activity, $input, $output);
      }

      // Import module here

      $moduleData['antispam'] = 1;
      $moduleData['checkboxfiche1201bf_activites_raw'] = $moduleData['checkboxfiche1201bf_activites'];
      if ($createModule) {
          $entryManager->create(1202, $moduleData);
      } else {
          $this->wiki->SavePage(
              $moduleId,
              json_encode($moduleData),
              '',
              true
          );
      }
    }

    protected function importCourse($courseId, $courseData, InputInterface $input, OutputInterface $output)
    {
      $entryManager = $this->wiki->services->get(EntryManager::class);
      if (is_null($localCourse = $entryManager->getOne($courseId))) {
          $output->writeln('<info>Importing course "' . $courseId . '"</>');
          $createCourse = true;
      } elseif ($this->force || $this->askWhenDuplicate($localCourse, $courseData, $input, $output)) {
          $output->writeln('<comment>Course "' . $courseId . '" already exists, updating it</>');
          $createCourse = false;
      } else {
          $output->writeln('<comment>Course "' . $courseId . '" already exists, not importing</>');
          return;
      }

      $this->downloadAttachments($courseData, $output, $input->getOption('peertube-url'));

      $course_modules = explode(',', $courseData['checkboxfiche1202bf_modules']);

      foreach ($course_modules as $course_module) {
          $module = $this->modules[$course_module];

          $this->importModule($course_module, $module, $input, $output);
      }

      // Import course here

      $courseData['antispam'] = 1;
      $courseData['checkboxfiche1202bf_modules_raw'] = $courseData['checkboxfiche1202bf_modules'];
      if ($createCourse) {
          $entryManager->create(1203, $courseData);
      } else {
          $this->wiki->SavePage(
              $courseId,
              json_encode($courseData),
              '',
              true
          );
      }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->remote_url = $input->getArgument('url');
        $this->remote_token = $input->getArgument('token');
        $this->force = $input->getOption('force');
        $askedCourses = $input->getOption('course');

        if (!filter_var($this->remote_url, FILTER_VALIDATE_URL)) {
            $output->writeln('<error>Error : first parameter URL must be a valid url</>');
            return Command::FAILURE;
        }

        if ($this->remote_url[-1] !== '/') {
            $this->remote_url .= '/';
        }

        //initialise peertube token
        if (!empty($this->wiki->config['peertube_url'])
          && !empty($this->wiki->config['peertube_user'])
          && !empty($this->wiki->config['peertube_password'])
          && !empty($this->wiki->config['peertube_channel'])
        ) {
            // get token from peertube
            $output->writeln('<info>Get Oauth client</>');
            $apiUrl = $this->wiki->config['peertube_url'].'/api/v1/oauth-clients/local';
            $data_str = @file_get_contents($apiUrl);
            $token = json_decode($data_str, true);

            if (!empty($token['client_id']) && !empty($token['client_secret'])) {
                // Get user token
                $data = [
                  'client_id' => $token['client_id'],
                  'client_secret' => $token['client_secret'],
                  'grant_type' => 'password',
                  'response_type' => 'code',
                  'username' => $this->wiki->config['peertube_user'],
                  'password' => $this->wiki->config['peertube_password'],
                ];
                $opts = array(
                    'http'=>array(
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'POST',
                        'content' => http_build_query($data)
                    )
                );
                $context = stream_context_create($opts);
                $apiUrl = $this->wiki->config['peertube_url'].'/api/v1/users/token';
                $data_str = @file_get_contents($apiUrl, false, $context);
                $token = json_decode($data_str, true);
                if (!empty($token['access_token'])) {
                    $this->peertube_token = $token['access_token'];
                    $output->writeln('<fg=cyan>Got access token : '.$this->peertube_token.'</>');
                } else {
                    $output->writeln('<error>Got no access token from : '.$apiUrl.'</>');
                }
            } else {
                $output->writeln('<error>Got no client credentials from : '.$apiUrl.'</>');
            }
        } else {
            $output->writeln('<info>Configuration : "peertube_url", "peertube_user", "peertube_password" or "peertube_channel" were not set in configuration file :'
              .' no local video imports.</>');
        }

        // Fetching all information needed
        if (false === $this->courses = $this->fetch_api('fiche/1203/html', 'courses', $output)) {
            return Command::FAILURE;
        }
        if (false === $this->modules = $this->fetch_api('fiche/1202/html', 'modules', $output)) {
            return Command::FAILURE;
        }
        if (false === $this->activities = $this->fetch_api('fiche/1201/html', 'activities', $output)) {
            return Command::FAILURE;
        }

        if (count($askedCourses) == 0) {
            // Letting the user choose which courses he wants
            $choices = ['all' => 'All the courses (default)'];
            foreach ($this->courses as $course_tag => $course) {
                $choices[$course_tag] = $course['bf_titre'];
            }

            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Please select the courses that you would like to import',
                $choices,
                'all'
            );
            $question->setMultiselect(true);

            $selectedCourses = array_values($helper->ask($input, $output, $question));
        } else {
            $choices = ['all'];
            foreach ($this->courses as $course_tag => $_) {
                $choices[] = $course_tag;
            }
            $selectedCourses = array_intersect($askedCourses, $choices);
        }

        if (in_array('all', $selectedCourses)) {
            $selectedCourses = array_keys($this->courses);
        } else {
            $selectedCourses = array_unique($selectedCourses);
        }

        $output->writeln('<info>You have just selected: ' . implode(', ', $selectedCourses) . '</>');


        $entryManager = $this->wiki->services->get(EntryManager::class);

        foreach ($selectedCourses as $selectedCourse) {
            $course = $this->courses[$selectedCourse];

            $this->importCourse($selectedCourse, $course, $input, $output);
        }

        return Command::SUCCESS;
    }
}
