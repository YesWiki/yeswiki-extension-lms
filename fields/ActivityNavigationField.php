<?php

namespace YesWiki\Lms\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Wiki;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Service\DateManager;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\ActivityNavigationConditionsManager;

/**
 * @Field({"navigationactivite","activitynavigation"})
 */
class ActivityNavigationField extends LmsField
{
    protected const LABEL_REACTION_NEEDED = 'reaction_needed';
    protected const LABEL_QUIZZ_DONE = 'quizz_done';
    protected const LABEL_NEW_VALUES = 'new_values';

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
    protected $ActivityNavigationConditionsManager;
    protected $moduleModal;
    protected $courseManager;
    protected $conditionsEnabled;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);

        
        $this->label = null;
        
        $this->config = $services->get(Wiki::class)->config ;
        $this->entryManager = $services->get(EntryManager::class);
        $this->dateManager = $services->get(DateManager::class);
        $this->courseManager = $services->get(CourseManager::class);
        $this->ActivityNavigationConditionsManager = $services->get(ActivityNavigationConditionsManager::class);
        
        // true if the module links are opened in a modal box
        $this->moduleModal = ($values[self::FIELD_MODAL] == 'module_modal');

        // activation of conditions
        $this->conditionsEnabled = (isset($this->config['lms_config']['activity_navigation_conditions_enabled']) &&
            ($this->config['lms_config']['activity_navigation_conditions_enabled'] == true));
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
            $learner = $this->learnerManager->getLearner();
            if ($learner
                && $this->courseManager->setModuleCanBeOpenedByLearner(
                    $learner,
                    $course,
                    $module
                ) // module should be accessible
                && ($referenceActivity = $module->getActivity($activity->getTag()))
                 // activity should be in module
                && !is_null($this->courseManager->setActivityCanBeOpenedByLearner(
                    $learner,
                    $course,
                    $module,
                    $referenceActivity
                )) // set Activity Can be opened
                && !is_null($activity->canBeOpenedBy($learner, $referenceActivity->canBeOpenedBy($learner)))
                && $activity->isAccessibleBy($learner, $course, $module)
            ) {
                // save the activity progress if not already exists for this user and activity
                $this->learnerManager->saveActivityProgress($course, $module, $activity);
            }

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
                if ($this->conditionsEnabled) {
                    // check conditions
                    $conditions = $this->ActivityNavigationConditionsManager
                        ->checkActivityNavigationConditions($course, $module, $activity, $entry) ;
                    $conditionsPassed = $conditions[ActivityNavigationConditionsManager::STATUS_LABEL] ?? false;
                    $conditionsMessage = $conditions[ActivityNavigationConditionsManager::MESSAGE_LABEL] ?? null;
                }
            }

            $output = $this->render("@lms/fields/activity-navigation.twig", [
                'activity' => $activity,
                'module' => $module,
                'course' => $course,
                'previousActivity' => $previousActivity ?? null,
                'nextModule' => $nextModule ?? null,
                'nextActivity' => $nextActivity ?? null,
                'conditionsEnabled' => $this->conditionsEnabled,
                'conditionsPassed' => $conditionsPassed ?? false,
                'conditionsMessage' => $conditionsMessage ?? null,
            ]);
        }
        return $output;
    }

    protected function renderInput($entry)
    {
        return ($this->conditionsEnabled) ?$this->render("@lms/inputs/activity-navigation.twig", [
            'value' => $this->getValue($entry),
            'entryId' => $entry['id_fiche'] ?? 'new',
            'options' => [
                self::LABEL_REACTION_NEEDED => _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_REACTION_NEEDED'),
                // self::LABEL_QUIZZ_DONE => _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZZ_DONE') //not ready
            ]
        ])
        : null;
    }

    // Format input values before save
    public function formatValuesBeforeSave($entry)
    {
        $value = $this->getValue($entry);
        $id_select = '';
        if (isset($value['id'])) {
            $id_select = $value['id'] . '_select';
        }
        if ($this->canEdit($entry) && is_array($value) && isset($value[self::LABEL_NEW_VALUES])) {
            $data = [];
            if (isset($value[self::LABEL_REACTION_NEEDED])) {
                $data[] = ['condition' => self::LABEL_REACTION_NEEDED];
            }
            if (isset($value[self::LABEL_QUIZZ_DONE])) {
                $data[] = ['condition' => self::LABEL_QUIZZ_DONE];
            }
            $value = $data ;
        }
        return (empty($value) || count($value) == 0)
            ? ['fields-to-remove' => ([$this->getPropertyName()] +
                (!empty($id_select)?[$id_select]:[]))]
            : ([$this->getPropertyName() => $value] +
                (!empty($id_select) ?['fields-to-remove' => [$id_select]]:[]));
    }

    protected function getValue($entry)
    {
        return $entry[$this->propertyName] ?? $_REQUEST[$this->propertyName] ?? $this->default;
    }
}
