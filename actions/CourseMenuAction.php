<?php

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiAction;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Controller\CourseController;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Wiki;

class CourseMenuAction extends YesWikiAction
{

    function run()
    {
        $courseController = $this->getService(CourseController::class);
        $courseManager = $this->getService(CourseManager::class);
        $entryManager = $this->getService(EntryManager::class);
        $config = $this->getService(ParameterBagInterface::class);
        $wiki = $this->getService(Wiki::class);

        // the course to display
        $course = $courseController->getContextualCourse();
        // the consulted module to display the current activity
        $module = $courseController->getContextualModule($course);

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

            // if an handler is after the page tag in the wiki parameter variable, get only the tag
            $pageTag = isset($_GET['wiki']) ?
                strpos($_GET['wiki'], '/') ?
                    substr($_GET['wiki'], 0, strpos($_GET['wiki'], '/'))
                    : $_GET['wiki']
                : null;

            if (!empty($pageTag)) {

                // the activity is not loaded from the manager because we don't want to requests the fields
                // (it's an exception)
                $activity = new Activity($config, $entryManager, $pageTag);
                // if nav tabs are configurated and if the current activity is a tab activity, we refer now to the
                // parent tab activity
                $activity = $courseController->getParentTabActivity($activity);

                // display the modules only if the current module is in the modules displayed
                $currentModuleInModules = !empty(array_filter(
                    $modulesDisplayed,
                    function ($item) use ($module) {
                        return $item->getTag() == $module->getTag();
                    }
                ));

                if ($currentModuleInModules) {
                    return $this->render('@lms/course-menu.twig',[
                        'pageTag' => $activity->getTag(),
                        'course' => $course,
                        'module' => $module,
                        'modulesDisplayed' => $modulesDisplayed,
                        'isAdmin' => $wiki->userIsAdmin() && !$config->get('lms_config')['admin_as_user']
                    ]);
                }
            }
        }
    }
}