<?php

namespace YesWiki\Lms\Service;

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiService;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\CourseStructure;
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
    protected $wiki;

    /**
     * CourseManager constructor
     * @param Wiki $wiki the injected Wiki instance
     * @param EntryManager $entryManager the injected EntryManager instance
     * @param UserManager $userManager the injected UserManager instance
     * @param DateManager $dateManager the injected UserManager instance
     */
    public function __construct(
        Wiki $wiki,
        EntryManager $entryManager,
        UserManager $userManager,
        DateManager $dateManager
    ) {
        $this->wiki = $wiki;
        $this->config = $wiki->config;
        $this->entryManager = $entryManager;
        $this->userManager = $userManager;
        $this->dateManager = $dateManager;
        $this->activityFormId = $this->config['lms_config']['activity_form_id'];
        $this->moduleFormId = $this->config['lms_config']['module_form_id'];
        $this->courseFormId = $this->config['lms_config']['course_form_id'];
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
        $courseEntry = $this->entryManager->getOne($entryTag);
        if ($courseEntry && intval($courseEntry['id_typeannonce']) == $this->courseFormId) {
            return new Course($this->config, $this->entryManager, $this->dateManager, $courseEntry['id_fiche'], $courseEntry);
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
                    return new Course($this->config, $this->entryManager, $this->dateManager, $courseEntry['id_fiche'], $courseEntry);
                },
                $entries
            );
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
            if (!$activity->isAccessibleBy($learner, $course, $module)) {
                // do not check for following of the module if one is false
                break;
            }
            $openableActivities[] = $activity ;
        }
        foreach ($openableActivities as $openableActivity) {
            if ($learner->hasOpened($course, $module, $openableActivity)) {
                $lastOpenedActivity = $openableActivity;
            } else {
                break;
            }
        }
        return isset($lastOpenedActivity) ? $lastOpenedActivity->getTag() : null ;
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

    
    /** getNextActivityOrModule
     * @param Course $course
     * @param Module $module
     * @param Activity $activity
     * @return CourseStructure next activity or module
     */
    public function getNextActivityOrModule(Course $course, Module $module, Activity $activity): ?CourseStructure
    {
        if ($activity->getTag() == $module->getLastActivityTag()) {
            if ($module->getTag() != $course->getLastModuleTag()) {
                return $course->getNextModule($module->getTag());
                // if the current page is the last activity of the module and the module is not the last one,
                // the next link is to the next module entry
                // (no next button is showed for the last activity of the last module)
            }
        } else {
            // otherwise, the current activity is not the last of the module and the next link is set to the next activity
            return $module->getNextActivity($activity->getTag());
        }
        return null;
    }
}
