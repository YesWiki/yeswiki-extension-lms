<?php

use YesWiki\Activity;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiAction;
use YesWiki\Lms\Controller\CourseController;

class CourseMenuAction extends YesWikiAction
{

    function run()
    {
        $courseController = $this->getService(CourseController::class);

        // the course to display
        $course = $courseController->getContextualCourse();
        // the consulted module to display the current activity
        $module = $courseController->getContextualModule($course);

        // Read the action parameters
        // css class for the action
        $class = !empty($this->arguments['class']) ? $this->arguments['class'] : null;

        // display the menu only if a contextual course and module are found
        if ($course && $module) {
            // first module to display
            // if not defined, or the one defined doesn't exist or isn't a module entry, the first module is by default
            // the first of the course
            $moduleDebutTag = !empty($this->arguments['moduledebut']) ?
                $this->arguments['moduledebut']
                : $course->getFirstModuleTag();
            // last module to display
            // if not defined, or the one defined doesn't exists or isn't a module entry, the last module is by default
            // the last of the course
            $moduleFinTag = !empty($this->arguments['modulefin']) ?
                $this->arguments['modulefin']
                : $course->getLastModuleTag();

            $modulesDisplayed = $course->getModulesBetween($moduleDebutTag, $moduleFinTag);

            // find the menu template
            $template = !empty($this->arguments['template']) ? $this->arguments['template'] : null;
            if (empty($template) || !file_exists(LMS_PATH . '/templates/' . $template)) {
                $template = "menu-lms.tpl.html";
            }

            // display the menu with the template
            include_once 'includes/squelettephp.class.php';
            try {
                $squel = new SquelettePhp($template, 'lms');
                $content = $squel->render(
                    array(
                        "course" => $course,
                        "currentModule" => $module,
                        "modulesDisplayed" => $modulesDisplayed,
                    )
                );
            } catch (Exception $e) {
                $content = '<div class="alert alert-danger">' . _t('LMS_COURSEMENU_ERROR') . $e->getMessage() . '</div>' . "\n";
            }

            return (!empty($class)) ? '<div class="' . $class . '">' . "\n" . $content . "\n" . '</div>' . "\n" : $content;
        }
    }
}