<?php
namespace YesWiki\Lms\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\PageManager;
use YesWiki\Wiki;


class ImportCoursesCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'lms:import-courses';

    protected $wiki;
    protected $remote_url;
    protected $remote_token;
    protected $upload_path;

    public function __construct(Wiki &$wiki)
    {
        parent::__construct();
        $this->wiki = $wiki;
    }

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Import courses from another YesWiki url.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to import courses, and related modules and activities from another YesWiki with LMS extension.')
            ->addArgument('url', InputArgument::REQUIRED, 'URL to another wiki you wish to copy')
            ->addArgument('token', InputArgument::REQUIRED, 'API token for that wiki, found in `wakka.config.php`')
        ;
    }

    private function fetch_bazar($form_id, $log_name, $output)
    {
        // Create a stream
        $opts = array(
            'http'=>array(
                'method' => 'GET',
                'header' => 'Authorization: Bearer ' . $this->remote_token . "\r\n"
            )
        );

        $context = stream_context_create($opts);

        // Fetching all information needed
        $output->writeln('<info>Fetching '.$log_name.'</>');
        $data_str = file_get_contents($this->remote_url.'?api/fiche/'.$form_id, false, $context);
        if (empty($data_str)) {
                $output->writeln('<error>Error : unable to fetch '.$log_name.'</>');
                return false;
        } else if (!$data_json=json_decode($data_str, true)) {
                var_dump($data_str);
                $output->writeln('<error>Error : unable to parse '.$log_name.'</>');
                return false;
        } else {
            $data = array();
            foreach ($data_json as $entry) {
                $data[$entry['id_fiche']] = $entry;
            }
        }

        return $data;
    }

    private function getLocalFileUploadPath()
    {
        if ($this->upload_path !== null) {
            return $this->upload_path;
        }

        $attachConfig = $this->wiki->GetConfigValue("attach_config");

        if (!is_array($attachConfig)) {
            $attachConfig = array();
        }

        if (empty($attachConfig['upload_path'])) {
            $this->upload_path = 'files';
        } else {
            $this->upload_path = $attachConfig['upload_path'];
        }

        return $this->upload_path;
    }

    private function importImage($filename, OutputInterface $output)
    {
        $output->writeln('<info>Importing image '.$filename.'</>');

        // Assuming the remote uses default file directory
        $image_url = $this->remote_url.'/files/'.$filename;

        $dest = $this->getLocalFileUploadPath();
        $save_file_loc = "$dest/$filename";

        if (file_exists($save_file_loc)) {
            $output->writeln('<comment>File '.$save_file_loc.' already exists in filesystem</>');
        } else {
            // Do cURL transfer
            $fp = fopen($save_file_loc, 'wb');
            $ch = curl_init($image_url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch,CURLOPT_FAILONERROR,true);
            curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            fclose($fp);

            if ($err) {
                $output->writeln('<error>Error downloading '.$filename.': '.$err);
                unlink($save_file_loc);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->remote_url = $input->getArgument('url');
        $this->remote_token = $input->getArgument('token');

        if (!filter_var($this->remote_url, FILTER_VALIDATE_URL)) {
            $output->writeln('<error>Error : first parameter URL must be a valid url</>');
            return Command::FAILURE;
        }

        if ($this->remote_url[-1] !== '/') {
            $this->remote_url .= '/';
        }

        // Fetching all information needed
        if (false === $courses = $this->fetch_bazar(1203, 'courses', $output))
            return Command::FAILURE;
        if (false === $modules = $this->fetch_bazar(1202, 'modules', $output))
            return Command::FAILURE;
        if (false === $activities = $this->fetch_bazar(1201, 'activities', $output))
            return Command::FAILURE;


        // Letting the user choose which courses he wants
        $choices = ['all' => 'All the courses (default)'];
        foreach ($courses as $course) {
            //$output->writeln($course['bf_titre']);
            $choices[$course['id_fiche']] = $course['bf_titre'];
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select the courses that you would like to import',
            $choices,
            'all'
        );
        $question->setMultiselect(true);

        $selectedCourses = array_values($helper->ask($input, $output, $question));

        if (in_array('all', $selectedCourses)) {
            $selectedCourses = array_keys($courses);
        } else {
            $selectedCourses = array_unique($selectedCourses);
        }

        $output->writeln('<info>You have just selected: ' . implode(', ', $selectedCourses) . '</>');


        $pageManager = $this->wiki->services->get(PageManager::class);
        $entryManager = $this->wiki->services->get(EntryManager::class);

        foreach ($selectedCourses as $selectedCourse) {
            $course = $courses[$selectedCourse];

            if (is_null($pageManager->getOne($selectedCourse, '', 0))) {
                $output->writeln('<info>Importing course "' . $selectedCourse . '"</>');
            } else {
                $output->writeln('<comment>Course "' . $selectedCourse . '" already exists, not importing</>');
                continue;
            }

            $course_modules = explode(',', $course['checkboxfiche1202bf_modules']);

            foreach ($course_modules as $course_module) {
                $module = $modules[$course_module];

                if (is_null($pageManager->getOne($course_module, '', 0))) {
                    $output->writeln('<info>Importing module "' . $course_module . '"</>');
                } else {
                    $output->writeln('<comment>Module "' . $course_module . '" already exists, not importing</>');
                    continue;
                }

                $module_activities = explode(',', $module['checkboxfiche1201bf_activites']);


                foreach ($module_activities as $module_activity) {
                    $activity = $activities[$module_activity];

                    if (is_null($pageManager->getOne($module_activity, '', 0))) {
                        $output->writeln('<info>Importing activity "' . $module_activity . '"</>');
                    } else {
                        $output->writeln('<comment>Activity "' . $module_activity . '" already exists, not importing</>');
                        continue;
                    }

                    // Import activity here
                    $activity['antispam'] = 1;
                    $entryManager->create(1201, $activity);
                }

                // Import module here

                // Checking for module image
                if(!empty($module['imagebf_image'])) {
                    $this->importImage($module['imagebf_image'], $output);
                }

                $module['antispam'] = 1;
                $module['checkboxfiche1201bf_activites_raw'] = $module['checkboxfiche1201bf_activites'];
                $entryManager->create(1202, $module);
            }

            // Import course here
            $course['antispam'] = 1;
            $course['checkboxfiche1202bf_modules_raw'] = $course['checkboxfiche1202bf_modules'];
            $entryManager->create(1203, $course);
        }

        return Command::SUCCESS;
    }
}
