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
            $output->writeln('Error URL TOKEN : first parameter URL must be a valid url');
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

        // Open the file using the HTTP headers set above
        $courses_str = file_get_contents($remote_url.'/?api/fiche/1203', false, $context);
        if (!empty($courses_str) && $courses=json_decode($courses_str, true)) {
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

            $selectedCourses = $helper->ask($input, $output, $question);
            $output->writeln('You have just selected: ' . implode(', ', $selectedCourses));
        } else {
            $output->writeln('Error : empty response or invalid json response for '.$remote_url.'/?api/fiche/1203'.': '."\n".$courses_str);
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
