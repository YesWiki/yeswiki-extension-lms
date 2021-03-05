<?php
namespace YesWiki\Lms\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;


class ImportCoursesCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'lms:import-courses';

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $remote_url = $input->getArgument('url');
        $remote_token = $input->getArgument('token');

        if (!filter_var($remote_url, FILTER_VALIDATE_URL)) {
            $output->writeln('<error>Error : first parameter URL must be a valid url</>');
            return Command::FAILURE;
        }

        // Create a stream
        $opts = array(
            'http'=>array(
                'method' => "GET",
                'header' => "Authorization: Bearer $remote_token\r\n"
            )
        );

        $context = stream_context_create($opts);

        // Fetching all information needed
        $output->writeln('<info>Fetching courses</>');
        $courses_str = file_get_contents($remote_url.'/?api/fiche/1203', false, $context);
        if (empty($courses_str) || !$courses_json=json_decode($courses_str, true)) {
                $output->writeln('<error>Error : unable to fetch courses</>');
                return Command::FAILURE;
        } else {
            $courses = array();
            foreach ($courses_json as $course) {
                $courses[$course['id_fiche']] = $course;
            }
        }

        $output->writeln('<info>Fetching modules</>');
        $modules_str = file_get_contents($remote_url.'/?api/fiche/1202', false, $context);
        if (empty($modules_str) || !$modules_json=json_decode($modules_str, true)) {
                $output->writeln('<error>Error : unable to fetch modules</>');
                return Command::FAILURE;
        } else {
            $modules = array();
            foreach ($modules_json as $module) {
                $modules[$module['id_fiche']] = $module;
            }
        }

        $output->writeln('<info>Fetching activities</>');
        $activities_str = file_get_contents($remote_url.'/?api/fiche/1201', false, $context);
        if (empty($activities_str) || !$activities_json=json_decode($activities_str, true)) {
                $output->writeln('<error>Error : unable to fetch activities</>');
                return Command::FAILURE;
        } else {
            $activities = array();
            foreach ($activities_json as $activity) {
                $activities[$activity['id_fiche']] = $activity;
            }
        }

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



        foreach ($selectedCourses as $selected) {
            $course = $courses[$selected];

            if (true/* TODO check if course already is in db */) {
                $output->writeln('<info>Importing course "' . $selected . '"</>');
            } else {
                $output->writeln('<comment>Course "' . $selected . '" already exists, not importing</>');
                continue;
            }

            $course_modules = explode(',', $course['checkboxfiche1202bf_modules']);

            foreach ($course_modules as $course_module) {
                $module = $modules[$course_module];

                if (true/* TODO check if module already is in db */) {
                    $output->writeln('<info>Importing module "' . $course_module . '"</>');
                } else {
                    $output->writeln('<comment>Module "' . $course_module . '" already exists, not importing</>');
                    continue;
                }

                $module_activities = explode(',', $module['checkboxfiche1201bf_activites']);


                foreach ($module_activities as $module_activity) {
                    $activity = $activities[$module_activity];

                    if (false/* TODO check if activity already is in db */) {
                        $output->writeln('<info>Importing activity "' . $module_activity . '"</>');
                    } else {
                        $output->writeln('<comment>Activity "' . $module_activity . '" already exists, not importing</>');
                        continue;
                    }

                    //TODO: Import activity here
                }

                //TODO: Import module here
            }

            //TODO: Import course here
        }

        return Command::SUCCESS;
    }
}
