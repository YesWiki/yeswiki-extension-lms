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
use YesWiki\Wiki;

if (!class_exists('attach')) {
    require("tools/attach/libs/attach.lib.php");
}

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

    public function __construct(Wiki &$wiki)
    {
        parent::__construct();
        $this->wiki = $wiki;
        $this->peertube_token = null;
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
                InputArgument::REQUIRED,
                'API token for that wiki, found in `wakka.config.php`')
            ->addOption(
                'force', 'f',
                InputOption::VALUE_NONE,
                'Will force updating of existing courses, modules and activities')
            ->addOption(
                'course', 'c',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Which courses to import')
        ;
    }

    private function fetch_api($api_args, $log_name, OutputInterface $output)
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
        $data_str = file_get_contents($this->remote_url.'?api/'.$api_args, false, $context);
        if (empty($data_str)) {
            $output->writeln('<error>Error : unable to fetch '.$log_name.'</>');
            return false;
        } elseif (!$data_json=json_decode($data_str, true)) {
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

    private function importToPeertube($url, $title, OutputInterface $output)
    {
        $output->writeln('<info>Importing video '.$title.' ('.$url.')</>');
        $channel = json_decode(file_get_contents($this->wiki->config['peertube_url'].'/api/v1/video-channels/'.$this->wiki->config['peertube_channel']), true);
        // Get user token
        $data = [
          'channelId' => $channel['id'],
          'name' => $title,
          'targetUrl' => $url,
        ];
        $opts = array(
            'http'=>array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n".
                             "Authorization: Bearer ".$this->peertube_token."\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($opts);
        $apiUrl = $this->wiki->config['peertube_url'].'/api/v1/videos/imports';
        $data_str = file_get_contents($apiUrl, false, $context);
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

    private function cURLDownload($from, $to, $force, OutputInterface $output)
    {
        if (file_exists($to)) {
            if ($this->force) {
                $output->writeln('<comment>File '.$to.' already exists in filesystem, overwriting</>');
            } else {
                $output->writeln('<comment>File '.$to.' already exists in filesystem, not downloading</>');
                return;
            }
        }

        // Do cURL transfer
        $fp = fopen($to, 'wb');
        $ch = curl_init($from);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($err) {
            $output->writeln('<error>Error downloading '.$filename.': '.$err);
            $output->writeln('Removing corrupted file '.$filename.'</>');
            unlink($to);
        }
    }

    private function downloadAttachments(&$bazarPage, OutputInterface $output)
    {
        // Handle Pictures and file attachments
        $force = $this->last_choice == 'r' || $this->last_choice == 'o';
        // Downloading images
        preg_match_all(
            '#(?:href|src)="'.preg_quote($this->remote_url, '#').'files/(.*)"#Ui',
            $bazarPage['html_output'],
            $matches
        );

        $images = array_filter($bazarPage, function ($k) {
            return str_starts_with($k, 'image');
        }, ARRAY_FILTER_USE_KEY);

        $attachments = array_merge($matches[1], array_values($images));
        $attachments = array_unique($attachments);

        if ($c = count($attachments)) {
            $output->writeln(
                '<info>Downloading '.$c.' image'.(($c>1)?'s':'').' for '.$bazarPage['id_fiche'].'</>'
            );

            $dest = $this->getLocalFileUploadPath();

            foreach ($attachments as $attachment) {
                $remote_file_url = $this->remote_url.'/files/'.$attachment;
                $save_file_loc = "$dest/$attachment";

                $this->cURLDownload($remote_file_url, $save_file_loc, $force, $output);
            }
        }

        // Downloading other attachments
        preg_match_all(
            '#(?:href|src)="'.preg_quote($this->remote_url, '#').'\?.+/download&(?:amp;)?file=(.*)"#Ui',
            $bazarPage['html_output'],
            $html_matches
        );
        $wiki_regex = '#url="' . preg_quote($this->remote_url, '#')
                      . '(\?.+/download&(?:amp;)?file=(.*))"#Ui';
        preg_match_all(
            $wiki_regex,
            (!empty($bazarPage['bf_contenu']) ? $bazarPage['bf_contenu'] : $bazarPage['bf_description']),
            $wiki_matches
        );

        $attachments = array_merge($html_matches[1], $wiki_matches[2]);
        $attachments = array_unique($attachments);

        if ($c = count($attachments)) {
            $output->writeln(
                '<info>Downloading '.$c.' attachment'.(($c>1)?'s':'').' for '.$bazarPage['id_fiche'].'</>'
            );
            $this->wiki->tag = $bazarPage['id_fiche'];
            $this->wiki->page = array('tag'=>$bazarPage['id_fiche'], 'time'=> $bazarPage['date_maj_fiche']);

            foreach ($attachments as $attachment) {
                $remote_file_url = $this->remote_url . '?' . $bazarPage['id_fiche'] . '/download&file=' . $attachment;
                $att = new \attach($this->wiki);
                $att->file = $attachment;
                $new_filename = $att->GetFullFilename(true);

                $this->cURLDownload($remote_file_url, $new_filename, $force, $output);
            }
        }

        $replaced = preg_replace(
            $wiki_regex,
            'url="'.$this->wiki->getBaseUrl().'/$1"',
            (!empty($bazarPage['bf_contenu']) ? $bazarPage['bf_contenu'] : $bazarPage['bf_description']),
        );
        if (!empty($bazarPage['bf_contenu'])) {
            $bazarPage['bf_contenu'] = $replaced;
        } else {
            $bazarPage['bf_description'] = $replaced;
        }

        // Handle Videos if a peertube location is configured
        if (!empty($this->peertube_token)) {
            $content = (!empty($bazarPage['bf_contenu']) ? $bazarPage['bf_contenu'] : $bazarPage['bf_description']);
            $video_wiki_regex = '#{{video(?:\s*(?:id="(?<id>\S+)"|serveur="(?<serveur>peertube|vimeo|youtube)"|peertubeinstance="(?<peertubeinstance>\S+)"|ratio="(?<ratio>.+)"|largeurmax="(?<largeurmax>\d+)"|hauteurmax="(?<hauteurmax>\d+)"| class="(?<class>.+)"))+\s*}}#i';
            preg_match_all(
                $video_wiki_regex,
                $content,
                $video_wiki_matches
            );

            $video_html_regex = '#<iframe.+?(?:\s*width=["\'](?<width>[^"\']+)["\']|\s*height=["\'](?<height>[^\'"]+)["\']|\s*src=["\'](?<src>[^\'"]+["\']))+[^>]*>(<\/iframe>)?#mi';
            preg_match_all(
                $video_html_regex,
                $content,
                $video_html_matches
            );

            if (!empty($video_wiki_matches['id'])) {
                foreach ($video_wiki_matches['id'] as $index => $videoId) {
                    // trouver l'instance video entre youtube|vimeo|peertube
                    // creer l'url de la video et la mettre dans $urlVideo
                    if (empty($video_wiki_matches['serveur'][$index])) {
                        if (strlen($videoId) == 11) {
                            $video_wiki_matches['serveur'][$index] = 'youtube';
                        } elseif (preg_match("/^\d+$/", $videoId)) {
                            $video_wiki_matches['serveur'][$index] = 'vimeo';
                        } else {
                            $video_wiki_matches['serveur'][$index] = 'peertube';
                        }
                    }
                    switch ($video_wiki_matches['serveur'][$index]) {
                        case 'youtube':
                            $urlVideo = 'https://youtu.be/'.$videoId;
                            $video = json_decode(file_get_contents('https://noembed.com/embed?url='.$urlVideo), true);
                            $titleVideo = $video['title'];
                            break;
                        case 'vimeo':
                            $urlVideo = 'https://vimeo.com/'.$videoId;
                            $video = json_decode(file_get_contents('https://noembed.com/embed?url='.$urlVideo), true);
                            $titleVideo = $video['title'];
                            break;
                        case 'peertube':
                            if (!empty($video_wiki_matches['peertubeinstance'][$index])) {
                                $urlVideo = $video_wiki_matches['peertubeinstance'][$index].'/videos/watch/'.$videoId;
                            } else {
                                $urlVideo = 'https://video.colibris-outilslibres.org/videos/watch/'.$videoId;
                            }
                            $video = json_decode(file_get_contents(str_replace('videos/watch', 'api/v1/videos', $urlVideo)), true);
                            $titleVideo = $video['name'];
                            break;

                        default:
                            $output->writeln('<error>Something went very wrong determining which provider the video included by "'.$video_wiki_matches[0][$index].'" has.');
                            die(1); // This part should never run, the switch should be handled by the cases above
                    }
                    $this->importToPeertube($urlVideo, $titleVideo, $output);
                }
            }

            if (!empty($video_html_matches['src'])) {
              // checker si l'url est une video youtube|vimeo|peertube
              // uploader
              echo 'TODO';
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
        if (false === $courses = $this->fetch_api('fiche/1203/html', 'courses', $output)) {
            return Command::FAILURE;
        }
        if (false === $modules = $this->fetch_api('fiche/1202/html', 'modules', $output)) {
            return Command::FAILURE;
        }
        if (false === $activities = $this->fetch_api('fiche/1201/html', 'activities', $output)) {
            return Command::FAILURE;
        }

        if (count($askedCourses) == 0) {
            // Letting the user choose which courses he wants
            $choices = ['all' => 'All the courses (default)'];
            foreach ($courses as $course_tag => $course) {
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
            foreach ($courses as $course_tag => $_) {
                $choices[] = $course_tag;
            }
            $selectedCourses = array_intersect($askedCourses, $choices);
        }

        if (in_array('all', $selectedCourses)) {
            $selectedCourses = array_keys($courses);
        } else {
            $selectedCourses = array_unique($selectedCourses);
        }

        $output->writeln('<info>You have just selected: ' . implode(', ', $selectedCourses) . '</>');


        $entryManager = $this->wiki->services->get(EntryManager::class);

        foreach ($selectedCourses as $selectedCourse) {
            $course = $courses[$selectedCourse];
            if (is_null($localCourse = $entryManager->getOne($selectedCourse))) {
                $output->writeln('<info>Importing course "' . $selectedCourse . '"</>');
                $createCourse = true;
            } elseif ($this->force || $this->askWhenDuplicate($localCourse, $course, $input, $output)) {
                $output->writeln('<comment>Course "' . $selectedCourse . '" already exists, updating it</>');
                $createCourse = false;
            } else {
                $output->writeln('<comment>Course "' . $selectedCourse . '" already exists, not importing</>');
                continue;
            }

            $this->downloadAttachments($course, $output);

            $course_modules = explode(',', $course['checkboxfiche1202bf_modules']);

            foreach ($course_modules as $course_module) {
                $module = $modules[$course_module];

                if (is_null($localModule = $entryManager->getOne($course_module))) {
                    $output->writeln('<info>Importing module "' . $course_module . '"</>');
                    $createModule = true;
                } elseif ($this->force || $this->askWhenDuplicate($localModule, $module, $input, $output)) {
                    $output->writeln('<comment>Module "' . $course_module . '" already exists, updating it</>');
                    $createModule = false;
                } else {
                    $output->writeln('<comment>Module "' . $course_module . '" already exists, not importing</>');
                    continue;
                }

                $this->downloadAttachments($module, $output);

                $module_activities = explode(',', $module['checkboxfiche1201bf_activites']);


                foreach ($module_activities as $module_activity) {
                    $activity = $activities[$module_activity];

                    if (is_null($localActivity = $entryManager->getOne($module_activity))) {
                        $output->writeln('<info>Importing activity "' . $module_activity . '"</>');
                        $createActivity = true;
                    } elseif ($this->force || $this->askWhenDuplicate($localActivity, $activity, $input, $output)) {
                        $output->writeln('<comment>Activity "' . $module_activity . '" already exists, updating it</>');
                        $createActivity = false;
                    } else {
                        $output->writeln('<comment>Activity "' . $module_activity . '" already exists, not importing</>');
                        continue;
                    }

                    // Import activity here
                    $this->downloadAttachments($activity, $output);

                    $activity['antispam'] = 1;
                    if ($createActivity) {
                        $entryManager->create(1201, $activity);
                    } else {
                        $this->wiki->SavePage(
                            $module_activity,
                            json_encode($activity),
                            '',
                            true
                        );
                    }
                }

                // Import module here

                $module['antispam'] = 1;
                $module['checkboxfiche1201bf_activites_raw'] = $module['checkboxfiche1201bf_activites'];
                if ($createModule) {
                    $entryManager->create(1202, $module);
                } else {
                    $this->wiki->SavePage(
                        $module_activity,
                        json_encode($activity),
                        '',
                        true
                    );
                }
            }

            // Import course here

            $course['antispam'] = 1;
            $course['checkboxfiche1202bf_modules_raw'] = $course['checkboxfiche1202bf_modules'];
            if ($createCourse) {
                $entryManager->create(1203, $course);
            } else {
                $this->wiki->SavePage(
                    $module_activity,
                    json_encode($activity),
                    '',
                    true
                );
            }
        }

        return Command::SUCCESS;
    }
}
