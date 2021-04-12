<?php

namespace YesWiki\Lms\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Wiki;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Module;
use YesWiki\Lms\Service\DateManager;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\ActivityNavigationConditionsManager;

/**
 * @Field({"navigationactivite","activitynavigation"})
 */
class ActivityNavigationField extends LmsField
{
    public const LABEL_REACTION_NEEDED = 'reaction_needed';
    public const LABEL_QUIZ_PASSED = 'quiz_passed';
    public const LABEL_QUIZ_PASSED_MINIMUM_LEVEL = 'quiz_passed_minimum_level';
    public const LABEL_QUIZ_MINIMUM_LEVEL = 'quiz_minimum_level';
    public const LABEL_QUIZ_ID = 'quizId';
    public const LABEL_FORM_FILLED = 'form_filled';
    public const LABEL_FORM_ID = 'formId';
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

    protected $wiki;
    protected $config;
    protected $entryManager;
    protected $dateManager;
    protected $ActivityNavigationConditionsManager;
    protected $moduleModal;
    protected $courseManager;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);

        
        $this->label = null;
        $this->default = [];
        
        $this->wiki = $services->get(Wiki::class) ;
        $this->config = $this->wiki->config ;
        $this->entryManager = $services->get(EntryManager::class);
        $this->dateManager = $services->get(DateManager::class);
        $this->courseManager = $services->get(CourseManager::class);
        $this->ActivityNavigationConditionsManager = $services->get(ActivityNavigationConditionsManager::class);
        
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
            $nextCourseStructure = $this->ActivityNavigationConditionsManager
                    ->getNextActivityOrModule($course, $module, $activity);
            if ($nextCourseStructure instanceof Module) {
                $nextModule = $nextCourseStructure;
            } else {
                $nextActivity = $nextCourseStructure;
            }
            
            // check conditions
            if ($this->courseManager->isConditionsEnabled()) {
                $conditions = $this->ActivityNavigationConditionsManager
                    ->checkActivityNavigationConditions($course, $module, $activity, $this->getValue($entry)) ;
                $conditionsStatus = $conditions[ActivityNavigationConditionsManager::STATUS_LABEL] ?? ActivityNavigationConditionsManager:: STATUS_CODE_ERROR;
                if ($conditionsStatus == ActivityNavigationConditionsManager::STATUS_CODE_OK_REACTIONS_NEEDED) {
                    $conditionsStatus = ActivityNavigationConditionsManager::STATUS_CODE_OK;
                    $reactionNeeded = true ;
                }
                $conditionsMessage = $conditions[ActivityNavigationConditionsManager::MESSAGE_LABEL] ?? null;
                if (($this->wiki->GetConfigValue('debug') == 'yes') && $conditionsStatus == ActivityNavigationConditionsManager:: STATUS_CODE_ERROR) {
                    trigger_error($conditionsMessage);
                }
            }

            $output = $this->render("@lms/fields/activity-navigation.twig", [
                'activity' => $activity,
                'module' => $module,
                'course' => $course,
                'previousActivity' => $previousActivity ?? null,
                'nextModule' => $nextModule ?? null,
                'nextActivity' => $nextActivity ?? null,
                'conditionsEnabled' => $this->courseManager->isConditionsEnabled(),
                'conditionsStatus' => $conditionsStatus ?? false,
                'conditionsMessage' => $conditionsMessage ?? null,
                'reactionNeeded' => $reactionNeeded ?? false,
            ]);
        }
        return $output;
    }

    protected function renderInput($entry)
    {
        return ($this->courseManager->isConditionsEnabled()) ? $this->render("@lms/inputs/activity-navigation.twig", [
            'value' => $this->getValue($entry),
            'entryId' => $entry['id_fiche'] ?? 'new',
            'options' => [
                self::LABEL_REACTION_NEEDED => _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_REACTION_NEEDED'),
                self::LABEL_QUIZ_PASSED => _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED'),
                self::LABEL_QUIZ_PASSED_MINIMUM_LEVEL => _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED_WITH_MINIMUM_LEVEL'),
                self::LABEL_FORM_FILLED => _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_FORM_FILLED')
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
            if (isset($value[self::LABEL_QUIZ_PASSED]) && isset($value[self::LABEL_QUIZ_PASSED][self::LABEL_QUIZ_ID])) {
                foreach ($value[self::LABEL_QUIZ_PASSED]['head'] as $id => $val) {
                    if (isset($value[self::LABEL_QUIZ_PASSED][self::LABEL_QUIZ_ID][$id])) {
                        $data[] = ['condition' => self::LABEL_QUIZ_PASSED,
                        self::LABEL_QUIZ_ID => $value[self::LABEL_QUIZ_PASSED][self::LABEL_QUIZ_ID][$id]];
                    }
                }
            }
            if (isset($value[self::LABEL_QUIZ_PASSED_MINIMUM_LEVEL])
                    && isset($value[self::LABEL_QUIZ_PASSED_MINIMUM_LEVEL][self::LABEL_QUIZ_ID])
                    && isset($value[self::LABEL_QUIZ_PASSED_MINIMUM_LEVEL][self::LABEL_QUIZ_MINIMUM_LEVEL])) {
                foreach ($value[self::LABEL_QUIZ_PASSED_MINIMUM_LEVEL]['head'] as $id => $val) {
                    if (isset($value[self::LABEL_QUIZ_PASSED_MINIMUM_LEVEL][self::LABEL_QUIZ_ID][$id])
                            && isset($value[self::LABEL_QUIZ_PASSED_MINIMUM_LEVEL][self::LABEL_QUIZ_MINIMUM_LEVEL][$id])) {
                        $data[] = ['condition' => self::LABEL_QUIZ_PASSED_MINIMUM_LEVEL,
                        self::LABEL_QUIZ_ID => $value[self::LABEL_QUIZ_PASSED_MINIMUM_LEVEL][self::LABEL_QUIZ_ID][$id],
                        self::LABEL_QUIZ_MINIMUM_LEVEL => $value[self::LABEL_QUIZ_PASSED_MINIMUM_LEVEL][self::LABEL_QUIZ_MINIMUM_LEVEL][$id]];
                    }
                }
            }
            if (isset($value[self::LABEL_FORM_FILLED]) && isset($value[self::LABEL_FORM_FILLED][self::LABEL_FORM_ID])) {
                foreach ($value[self::LABEL_FORM_FILLED]['head'] as $id => $val) {
                    if (isset($value[self::LABEL_FORM_FILLED][self::LABEL_FORM_ID][$id])) {
                        $data[] = ['condition' => self::LABEL_FORM_FILLED,
                        self::LABEL_FORM_ID => $value[self::LABEL_FORM_FILLED][self::LABEL_FORM_ID][$id]];
                    }
                }
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
