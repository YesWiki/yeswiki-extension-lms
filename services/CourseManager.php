<?php

namespace YesWiki\Lms\Service;

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiService;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Learner;
use YesWiki\Lms\ModuleStatus;
use YesWiki\Lms\Module;
use YesWiki\Wiki;

class CourseManager
{
    protected $config;
    protected $entryManager;
    protected $userManager;
    protected $dateManager;
    protected $activityFormId;
    protected $moduleFormId;
    protected $courseFormId;
    protected $learnerManager;
    protected $activityNavigationConditionsManager;
    protected $coursesCache ;
    protected $conditionsEnabled ;

    /**
     * CourseManager constructor
     * @param Wiki $wiki the injected Wiki instance
     * @param EntryManager $entryManager the injected EntryManager instance
     * @param UserManager $userManager the injected UserManager instance
     * @param DateManager $dateManager the injected UserManager instance
     * @param LearnerManager $learnerManager the injected LearnerManager instance
     */
    public function __construct(
        Wiki $wiki,
        EntryManager $entryManager,
        UserManager $userManager,
        DateManager $dateManager,
        LearnerManager $learnerManager
    ) {
        $this->wiki = $wiki;
        $this->config = $wiki->config;
        $this->entryManager = $entryManager;
        $this->userManager = $userManager;
        $this->dateManager = $dateManager;
        $this->learnerManager = $learnerManager;
        $this->activityFormId = $this->config['lms_config']['activity_form_id'];
        $this->moduleFormId = $this->config['lms_config']['module_form_id'];
        $this->courseFormId = $this->config['lms_config']['course_form_id'];
        $this->coursesCache = [];
        $this->conditionsEnabled = $this->config['lms_config']['activity_navigation_conditions_enabled'] ?? false ;
    }

    /**
     * Load an Activity from its entry tag
     * @param string $entryTag the entry tag corresponding to the activity
     * @param array|null $activityFields the activity fields if needed to populate directly the object
     * @return Activity|null the activity or null if the entry is not an activity
     */
    public function getActivity(string $entryTag, array $activityFields = null): ?Activity
    {
        $activityEntry = $this->entryManager->getOne($entryTag);
        if ($activityEntry && intval($activityEntry['id_typeannonce']) == $this->activityFormId) {
            return new Activity($this->config, $this->entryManager, $this->dateManager, $activityEntry['id_fiche'], $activityEntry);
        } else {
            return null;
        }
    }

    /**
     * Load a Module from its entry tag
     * @param string $entryTag the entry tag corresponding to the module
     * @param array|null $moduleFields the module fields if needed to populate directly the object
     * @return Module|null the module or null if the entry is not a module
     */
    public function getModule(string $entryTag, array $moduleFields = null): ?Module
    {
        $moduleEntry = $this->entryManager->getOne($entryTag);
        if ($moduleEntry && intval($moduleEntry['id_typeannonce']) == $this->moduleFormId) {
            return new Module($this->config, $this->entryManager, $this->dateManager, $moduleEntry['id_fiche'], $moduleEntry);
        } else {
            return null;
        }
    }

    /**
     * Load a Course from its entry tag
     * @param string $entryTag the entry tag corresponding to the course
     * @param array|null $courseFields the course fields if needed to populate directly the object
     * @return Module|null the course or null if the entry is not a course
     */
    public function getCourse(string $entryTag, array $courseFields = null): ?Course
    {
        if (isset($this->coursesCache[$entryTag])) {
            return $this->coursesCache[$entryTag] ;
        }
        $courseEntry = $this->entryManager->getOne($entryTag);
        if ($courseEntry && intval($courseEntry['id_typeannonce']) == $this->courseFormId) {
            $this->coursesCache[$entryTag] = new Course($this->config, $this->entryManager, $this->dateManager, $entryTag, $courseEntry);
            return $this->coursesCache[$entryTag] ;
        } else {
            return null;
        }
    }

    /**
     * Get all existing Course
     * @return Course[] the list of Course
     */
    public function getAllCourses(): array
    {
        $entries = $this->entryManager->search(['formsIds' => [$this->courseFormId]]);

        return empty($entries) ?
            [] :
            array_map(
                function ($courseEntry) {
                    if (isset($this->coursesCache[$courseEntry['id_fiche']])) {
                        return $this->coursesCache[$courseEntry['id_fiche']] ;
                    }
                    $this->coursesCache[$courseEntry['id_fiche']] = new Course($this->config, $this->entryManager, $this->dateManager, $courseEntry['id_fiche'], $courseEntry);
                    return $this->coursesCache[$courseEntry['id_fiche']] ;
                },
                $entries
            );
    }

    /**
     * set the module scriptedOpenedStatus for learner
     * @param Learner $learner
     * @param Course $course
     * @param Module $module
     * @return bool|null
     */
    public function setModuleCanBeOpenedByLearner(Learner $learner, Course $course, Module $module): ?bool
    {
        if (!is_null($module->canBeOpenedBy($learner))) {
            return $module->canBeOpenedBy($learner);
        }

        return $module->canBeOpenedBy(
            $learner,
            $this->checkModuleCanBeOpenedByLearner($learner, $course, $module)
        );
    }

    /**
     * checkModuleCanBeOpenedByLearner without checking condition and without save
     * @param Learner $learner
     * @param Course $course
     * @param Module $module
     * @param bool $checkConditions
     * @return bool|null
     */
    public function checkModuleCanBeOpenedByLearner(Learner $learner, Course $course, Module $module, bool $checkConditions= true): ?bool
    {
        $checkConditions = (!$this->isConditionsEnabled()) ? false : $checkConditions; // TODO really needed ?
        return !$course->isModuleScripted() //no constraint
            || !($previousModule = $course->getPreviousModule($module->getTag())) // or scripted but no previous module
            || (
                $this->learnerManager->hasBeenOpenedBy($course, $previousModule, null, $learner) // previous module should be opened
                && (
                    !($previousActivity = $previousModule->getLastActivity()) // scripted with empty but opened previous module
                    || $this->learnerManager->hasBeenOpenedBy($course, $previousModule, $previousActivity, $learner)
                        // or scripted and has started the last Activity of the previous module
                )
            ) ;
    }

    /**
     * check disabled link for module
     * @param Learner|null $learner
     * @param Course $course
     * @param Module $module
     * @return bool
     */
    public function isModuleDisabledLink(?Learner $learner = null, Course $course, Module $module):bool
    {
        if ($learner) {
            $this->setModuleCanBeOpenedByLearner($learner, $course, $module);
        }
        return !$module->isAccessibleBy($learner, $course) || $module->getStatus($course) == ModuleStatus::UNKNOWN;
    }

    /**
     * set the activity scriptedOpenedStatus for learner
     * @param Learner $learner
     * @param Course $course
     * @param Module $module
     * @param Activity $activity
     * @return bool|null
     */
    public function setActivityCanBeOpenedByLearner(Learner $learner, Course $course, Module $module, Activity $activity):?bool
    {
        if (!is_null($activity->canBeOpenedBy($learner))) {
            return $activity->canBeOpenedBy($learner);
        }

        return $activity->canBeOpenedBy(
            $learner,
            $this->checkActivityCanBeOpenedByLearner($learner, $course, $module, $activity)
        );
    }

    /**
     * checkActivityCanBeOpenedByLearner without checking condition and without save
     * @param Learner $learner
     * @param Course $course
     * @param Module $module
     * @param Activity $activity
     * @param bool $checkConditions
     * @return bool|null
     */
    public function checkActivityCanBeOpenedByLearner(Learner $learner, Course $course, Module $module, Activity $activity, bool $checkConditions= true): ?bool
    {
        if (is_null($this->activityNavigationConditionsManager)) {
            $this->activityNavigationConditionsManager = $this->wiki->services->get(ActivityNavigationConditionsManager::class);
        }
        $checkConditions = (!$this->isConditionsEnabled()) ? false : $checkConditions;
        return (
                !$course->isModuleScripted() //no constraint
                || (
                    $this->setModuleCanBeOpenedByLearner($learner, $course, $module) // set state for this module,
                    && $module->isAccessibleBy($learner, $course) // module accessible if scripted
                )
            )
            && (
                !$course->isActivityScripted() //no constraint
                || !($previousActivity = $module->getPreviousActivity($activity->getTag())) // or scripted but no previous activity
                || (
                    $this->learnerManager->hasBeenOpenedBy($course, $module, $previousActivity, $learner) // previous activity should be opened
                    && (
                        !$checkConditions ? true
                        : $this->activityNavigationConditionsManager
                            ->passActivityNavigationConditions($course, $module, $previousActivity, $activity)
                    )
                )
            );
    }

    /**
     * set the recursively activity and modules scriptedOpenedStatus for learner
     * @param Learner $learner
     * @param Course $course
     * @param Module[] $modules
     */
    public function setModulesScriptedOpenedStatus(Learner $learner, Course $course, array $modules)
    {
        foreach ($modules as $module) {
            if ($this->setModuleCanBeOpenedByLearner($learner, $course, $module)) {
                // define status only if can be opened
                foreach ($module->getActivities() as $activity) {
                    if (!$this->setActivityCanBeOpenedByLearner($learner, $course, $module, $activity)) {
                        // do not check for following of the module if one is false
                        break;
                    }
                }
            }
        }
    }

    /**
     * return if conditions are enabled
     * @return bool
     */
    public function isConditionsEnabled(): bool
    {
        return $this->conditionsEnabled;
    }
    /**
     * getLastAccessibleActivityTagForLearner for a module
     * @param Learner $learner
     * @param Course $course
     * @param Module $module
     * @return string|null tag of the activity
     */
    public function getLastAccessibleActivityTagForLearner(Learner $learner, Course $course, Module $module)
    {
        $openableActivities = [];
        foreach ($module->getActivities() as $activity) {
            if (!$this->setActivityCanBeOpenedByLearner($learner, $course, $module, $activity)) {
                // do not check for followin of the module if one is false
                break;
            }
            $openableActivities[] = $activity ;
        }
        foreach ($openableActivities as $openableActivity) {
            if ($this->learnerManager->hasBeenOpenedBy($course, $module, $openableActivity, $learner)) {
                $lastOpenedActivity = $openableActivity;
            } else {
                break;
            }
        }
        return isset($lastOpenedActivity) ? $lastOpenedActivity->getTag() : $module->getFirstActivityTag() ;
    }

    /**
     * getLastAccessibleActivityTagAndLabelForLearner
     * @param Learner|null $learner
     * @param Course $course
     * @param Module $module
     * @return array ['tag' => "activity's tag",'label' => 'label']
     */
    public function getLastAccessibleActivityTagAndLabelForLearner(?Learner $learner = null, Course $course, Module $module): array
    {
        if ($learner) {
            $nextActivityTag = $this->getLastAccessibleActivityTagForLearner($learner, $course, $module) ;
            $isFinished = (
                $module->getLastActivityTag() == $nextActivityTag
                && ($nextModule = $course->getNextModule($module->getTag()))
                && $this->learnerManager->hasBeenOpenedBy($course, $nextModule, null, $learner)
            );
        }
        $labelStart = $learner && $learner->isAdmin() && $module->getStatus($course) != ModuleStatus::OPEN ?
            _t('LMS_BEGIN_ONLY_ADMIN')
            : (!$learner || ($module->getFirstActivityTag() == $nextActivityTag) ?  _t('LMS_BEGIN')
                : ($isFinished ? _t('LMS_RESTART') : _t('LMS_RESUME')));
        if (!$learner || $isFinished) {
            $nextActivityTag = $module->getFirstActivityTag();
        }
        return [
            'tag' => $nextActivityTag,
            'label' => $labelStart
        ];
    }

    
    /**
     * getActivityParents
     * @param array $entry
     * @return array [['course'=>courseTag],['module'=>moduleTag],['course'=>courseTag,'module'=>moduleTag]]
     */
    public function getActivityParents(array $entry):array
    {
        if (!isset($entry['id_fiche'])) {
            return [];
        }

        $parents = [];
        foreach ($this->getAllCourses() as $course) {
            $courseFound = false;
            foreach ($course->getModules() as $module) {
                if ($module->hasActivity($entry['id_fiche'])) {
                    if (!$courseFound) {
                        $parents[] = ['course'=>$course->getTag()];
                    }
                    $courseFound = true;
                    $parents[] = ['course'=>$course->getTag(),'module'=>$module->getTag()];
                    $parents[] = ['module'=>$module->getTag()];
                }
            }
        }
        return $parents;
    }
}
