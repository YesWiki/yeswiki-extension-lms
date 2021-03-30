<?php

namespace YesWiki\Lms\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Wiki;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Service\DateManager;

/**
 * @Field({"navigationactivite","activitynavigation"})
 */
class ActivityNavigationField extends LmsField
{
    protected const FIELD_MODAL = 2;
    /**
     * Display the 'Précédent', 'Suivant' and 'Fait !' buttons which permits to a learner to navigate in an activity page
     * Must be declare in the bazar form definition as followed :
     *    'navigationactivite***bf_navigation*** *** *** *** *** *** *** *** ***'
     * The second position value is the name of the entry field.
     * If the word 'module_modal' is written at the third position, the links which refer to the modules are opened in a
     * modal box.
     */

    protected $config;
    protected $entryManager;
    protected $dateManager;
    protected $moduleModal;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);

        
        $this->label = null;
        
        $this->config = $services->get(Wiki::class)->config ;
        $this->entryManager = $services->get(EntryManager::class);
        $this->dateManager = $services->get(DateManager::class);
        
        // true if the module links are opened in a modal box
        $this->moduleModal = ($values[self::FIELD_MODAL] == 'module_modal');
    }

    // Render the show view of the field
    protected function renderStatic($entry)
    {
        $currentActivityTag = $this->getCurrentTag($entry);
        if (is_null($currentActivityTag)) {
            return null;
        }

        // the activity is not loaded from the manager because we don't want to requests the fields (it's an exception)
        $activity = new Activity($this->config, $this->entryManager, $this->dateManager, $currentActivityTag);

        // if nav tabs are configurated and if the current activity is a tab activity, we refer now to the parent tab activity
        $activity = $this->courseController->getParentTabActivity($activity);

        // the consulted course entry
        $course = $this->courseController->getContextualCourse();
        // the consulted module entry to display the current activity
        $module = $this->courseController->getContextualModule($course);

        $output = '';
        if ($course && $module && !empty($module->getActivities())) {
            // save the activity progress if not already exists for this user and activity
            $this->learnerManager->saveActivityProgress($course, $module, $activity);

            // display the previous button
            if ($activity->getTag() != $module->getFirstActivityTag()) {
                $previousActivity = $module->getPreviousActivity($activity->getTag());
            }

            // display the next button
            if ($activity->getTag() == $module->getLastActivityTag()) {
                if ($module->getTag() != $course->getLastModuleTag()) {
                    $nextModule = $course->getNextModule($module->getTag());
                    // if the current page is the last activity of the module and the module is not the last one,
                    // the next link is to the next module entry
                    // (no next button is showed for the last activity of the last module)
                }
            } else {
                // otherwise, the current activity is not the last of the module and the next link is set to the next activity
                $nextActivity = $module->getNextActivity($activity->getTag());
            }

            $output = $this->render("@lms/fields/activitynavigation.twig", [
                'activity' => $activity,
                'module' => $module,
                'course' => $course,
                'previousActivity' => $previousActivity ?? null,
                'nextModule' => $nextModule ?? null,
                'nextActivity' => $nextActivity ?? null,
            ]);
        }
        return $output;
    }
}