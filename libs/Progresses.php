<?php

namespace YesWiki\Lms;

class Progresses extends TimeLogs
{
    // an array which have a value for the following keys : 'username', 'course', 'module', 'activity', 'log_time'
    // the 'activity' key can be unset, on this case the progress relates to the module
    // 'elapsed_time' can also be filled out for 'module' only ('activity' is then unset)
    protected $values;

    /**
     * Return all usernames of the learner with no duplicates
     * @return array the usernames of the learner
     */
    public function getAllUsernames(): array
    {
        return array_unique(array_column($this->values, 'username'));
    }

    /* FILTERING FUNCTIONS */

    public function getProgressForActivityOrModuleForLearner(
        Learner $learner,
        Course $course,
        Module $module,
        ?Activity $activity
    ): ?array {
        $results = array_filter($this->values, function ($value) use ($learner, $course, $module, $activity) {
            return $value['username'] == $learner->getUsername()
                && $value['course'] == $course->getTag()
                && $value['module'] == $module->getTag()
                && ((!$activity && !isset($value['activity']))
                    || ($activity && isset($value['activity']) && $value['activity'] == $activity->getTag()));
        });
        return empty($results) ? null : array_values($results)[0];
    }

    public function getActivityProgressesOfModule(Course $course, Module $module): Progresses
    {
        return array_filter($this->values, function ($value) use ($course, $module) {
            return $value['course'] == $course->getTag()
                && $value['module'] == $module->getTag()
                && isset($value['activity']);
        });
    }

    /* EXTRACTING FUNCTIONS */

    /**
     * Get the usernames of the learner who have finished an activity
     * To considered finished, the next activity must have a progress. If it's the last activity of a module, it's the
     * next module or the first activity of the next module which mush have a progress. The last activity of the last
     * module is considered finished if it has a progress (the learner do it just by access it).
     * @param Course $course the course which contains the module
     * @param Module $module the module which contains the activity
     * @param Activity $activity the activity that learners must have finished
     * @return array the array of username (string)
     */
    public function getUsernamesForFinishedActivity(Course $course, Module $module, Activity $activity): array
    {
        $progresses = [];
        if ($course->hasModule($module->getTag()) && $module->hasActivity($activity->getTag())) {
            if ($activity->getTag() != $module->getLastActivityTag()) {
                // if the activity is not the last of the module, select the progresses for the next activity
                $nextActivityTag = $module->getNextActivity($activity->getTag())->getTag();
                $progresses = new Progresses(
                    array_filter($this->values, function ($value) use ($course, $module, $nextActivityTag) {
                        return $value['course'] == $course->getTag()
                            && $value['module'] == $module->getTag()
                            && isset($value['activity']) && $value['activity'] == $nextActivityTag;
                    })
                );
            } else {
                if ($module->getTag() != $course->getLastModuleTag()) {
                    // if it's the last activity and not the last module, select the progresses for the next module or
                    // the first activity of the next module
                    $nextModule = $course->getNextModule($module->getTag());
                    $progresses = new Progresses(
                        array_filter($this->values, function ($value) use ($course, $nextModule) {
                            return (
                                    $value['course'] == $course->getTag()
                                    && $value['module'] == $nextModule->getTag()
                                    && !isset($value['activity'])
                                ) ||  (
                                    !empty($nextModule->getActivities())
                                    && $value['course'] == $course->getTag()
                                    && $value['module'] == $nextModule->getTag()
                                    && isset($value['activity'])
                                    && $value['activity'] == $nextModule->getFirstActivityTag()
                                );
                        })
                    );
                } else {
                    // if it's the last activity and the last module, select the progresses for this activity
                    $progresses = $progresses = new Progresses(
                        array_filter($this->values, function ($value) use ($course, $module, $activity) {
                            return $value['course'] == $course->getTag()
                                && $value['module'] == $module->getTag()
                                && isset($value['activity']) && $value['activity'] == $activity->getTag();
                        })
                    );
                }
            }
        }
        return $progresses ? $progresses->getAllUsernames() : [];
    }
}
