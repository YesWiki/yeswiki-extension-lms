<?php

namespace YesWiki\Lms\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Lms\ModuleStatus;
use YesWiki\Lms\Service\CourseManager;

/**
 * @Field({"navigationmodule","modulenavigation"})
 */
class ModuleNavigationField extends LmsNavigationField
{
    /**
     * Display the different options to navigate into a module according to module field 'Activé' and the navigation of the learner.
     * Must be declare in the bazar form definition as followed :
     *    'navigationmodule**bf_navigation*** *** *** *** *** *** *** *** ***'
     * The second position value is the name of the entry field.
     */

    protected $courseManager;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);
        $this->courseManager = $services->get(CourseManager::class);
        
        // does the entry is viewed inside a modal box ? $moduleModal is true when the page was called in ajax
        $this->moduleModal = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    // Render the show view of the field
    protected function renderStatic($entry)
    {
        $currentModuleTag = $this->getCurrentTag($entry);
        if (is_null($currentModuleTag)) {
            return null;
        }
        
        // the consulted course entry
        $course = $this->courseController->getContextualCourse();
        // the consulted module entry to display the current activity
        $module = $this->courseManager->getModule($currentModuleTag, $entry);

        $output = '';
        if ($course && $module) {
            // save the activity progress if not already exists for this user and activity
            $this->learnerManager->saveModuleProgress($course, $module);

            // TODO duplicate code ($courseController->renderModuleCard) : when passing to twig, mutualize it

            $learner = $this->learnerManager->getLearner();
            $disabledLink = !$module->isAccessibleBy($learner, $course) || $module->getStatus($course) == ModuleStatus::UNKNOWN;

            // TODO implement getNextActivity for a learner, for the moment choose the first activity of the module
            $labelStart = $learner && $learner->isAdmin() && $module->getStatus($course) != ModuleStatus::OPEN ?
                _t('LMS_BEGIN_ONLY_ADMIN')
                : _t('LMS_BEGIN');
            $statusMsg = $this->courseController->calculateModuleStatusMessage($course, $module);

            // End of duplicate code

            // display the module next button
            if ($currentModuleTag != $course->getLastModuleTag()) {
                // if not the last module of the course, a link to the next module is displayed
                $nextModule = $course->getNextModule($currentModuleTag);
            }

            // display the module previous button
            if ($currentModuleTag != $course->getFirstModuleTag()) {
                // if not the first module of the course, a link to the previous module is displayed
                $previousModule = $course->getPreviousModule($currentModuleTag);
            }

            $output = $this->render("@lms/fields/modulenavigation.twig", [
                'disabledLink' => $disabledLink,
                'statusMsg' => $statusMsg,
                'labelStart' => $labelStart,
                'currentModuleTag' => $currentModuleTag,
                'course' => $course,
                'module' => $module,
                'nextModule' => $nextModule ?? null,
                'previousModule' => $previousModule ?? null,
            ]);
        }
        return $output;
    }
}
