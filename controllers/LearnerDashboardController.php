<?php

namespace YesWiki\Lms\Controller;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiController;
use YesWiki\Lms\Course;
use YesWiki\Lms\Learner;
use YesWiki\Lms\Module;
use YesWiki\Lms\Progresses;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\DateManager;

class LearnerDashboardController extends YesWikiController
{
    protected $userManager;
    protected $courseManager;
    protected $dateManager;

    /**
     * LearnerDashboardController constructor
     * @param UserManager $userManager the injected UserManager instance
     * @param CourseManager $courseManager the injected CourseManager instance
     * @param DateManager $dateManager the injected DateManager instance
     */
    public function __construct(
        UserManager $userManager,
        CourseManager $courseManager,
        DateManager $dateManager
    ) {
        $this->userManager = $userManager;
        $this->courseManager = $courseManager;
        $this->dateManager = $dateManager;
    }

    /**
     * Process stats for learner dashboard basing on array of Courses
     * @param $courses array of Course
     * @param $learner learner
     * @return array coursesStat = [
     *       "courseTag" => [
     *              ... ,
     *              "modulesStat" => ...
     *          ]
     *       ]
     */
    public function processCoursesStat(array $courses, Learner $learner): array
    {
        $coursesStat = [];
        foreach ($courses as $course) {
            $modulesStat = $this->processModulesStat($course, $learner);

            $started = !empty(array_filter($modulesStat, function ($moduleStat) {
                return $moduleStat['started'];
            }));

            $nbModulesFinished = count(array_filter($modulesStat, function ($moduleStat) {
                return $moduleStat['finished'];
            }));
            $nbModules = count($modulesStat);

            $finished = ($nbModulesFinished == $nbModules);

            $progressRatio = ($nbModules > 0) ? round($nbModulesFinished / $nbModules * 100) : 0;

            $duration = CarbonInterval::minutes(0);
            foreach ($modulesStat as $moduleStat) {
                if (isset($moduleStat['elapsedTime']) && $moduleStat['finished']) {
                    $duration->add($moduleStat['elapsedTime']);
                }
            }
            $courseDuration = ($duration->totalMinutes == 0) ? null : $duration;

            $coursesStat[$course->getTag()] = [
                "started" => $started, // bool
                "finished" => $finished, //bool
                "progressRatio" => $progressRatio, // int between 0 and 100 in pourcent
                "elapsedTime" => $courseDuration, // CarbonInterval object,
                "firstAccessDate" => $this->findFirstAccessDate($modulesStat), // Carbon object,
                "modulesStat" => $modulesStat
            ];
        }
        return $coursesStat;
    }

    /**
     * Process modules stats for learner dashboard basing on one course
     * @param $course course
     * @param $learner learner
     * @return array modulesStat = [
     *       "moduleTag" => [
     *              ... ,
     *              "activitiesStat" => ...
     *          ]
     *       ]
     */
    private function processModulesStat(Course $course, Learner $learner): array
    {
        $modulesStat = [];
        $modules = $course->getModules();
        $progresses = $learner->getAllProgresses();

        foreach ($modules as $module) {
            $activitiesStat = $this->processActivitiesStat($course, $module, $learner, $progresses);
            // get progress
            $progress = $progresses->getProgressForActivityOrModuleForLearner(
                $learner,
                $course,
                $module,
                null
            );

            $started = $progress || !empty(array_filter($activitiesStat, function ($activityStat) {
                    return $activityStat['started'];
                }));

            $nbActivities = count($activitiesStat);
            if ($nbActivities > 0) {
                $nbActivitiesFinished = count(array_filter($activitiesStat, function ($activityStat) {
                    return $activityStat['finished'];
                }));

                $finished = ($nbActivitiesFinished == $nbActivities);
                $progressRatio = round($nbActivitiesFinished / $nbActivities * 100);
            } else {
                $finished = false; // TODO take in count following module
                $progressRatio = $finished ? 100 : 0;
            }

            // the first access date displayed is the earliest log_time between the module and all its activities
            $firstAccessDate = !empty($progress['log_time']) ?
                $this->dateManager->createDateFromString($progress['log_time'])
                : null;
            $firstActivityAccessDate = $this->findFirstAccessDate($activitiesStat);
            if (($firstActivityAccessDate && $firstActivityAccessDate->lessThan($firstAccessDate))
                || (!$firstAccessDate && $firstActivityAccessDate)
            ) {
                $firstAccessDate = $firstActivityAccessDate;
            }

            if (!empty($progress['elapsed_time'])) {
                $moduleDuration = $this->dateManager->createIntervalFromString($progress['elapsed_time']);
            } else {
                $duration = CarbonInterval::minutes(0);
                foreach ($activitiesStat as $activityStat) {
                    if (!empty($activityStat['elapsedTime']) && $activityStat['finished']) {
                        $duration->add($activityStat['elapsedTime']);
                    }
                }
                $moduleDuration = ($duration->totalMinutes == 0) ? null : $duration;
            }

            $modulesStat[$module->getTag()] = [
                "started" => $started, // bool
                "finished" => $finished, //bool
                "progressRatio" => $progressRatio, // int between 0 and 100 in pourcent
                "elapsedTime" => $moduleDuration, // CarbonInterval object,
                "firstAccessDate" => $firstAccessDate, // Carbon object,
                "activitiesStat" => $activitiesStat
            ];
        }
        return $modulesStat;
    }

    /**
     * Process activities stats for learner dashboard basing on one module
     * @param $course course
     * @param $module module
     * @param $learner learner
     * @param $progresses Progresses used to found personal data
     * @return array activitiesStat = [
     *       "activityTag" => [
     *              ...
     *          ]
     *       ]
     */
    private function processActivitiesStat(
        Course $course,
        Module $module,
        Learner $learner,
        Progresses $progresses
    ): array {
        $activitiesStat = [];
        foreach ($module->getActivities() as $activity) {
            // get progress
            $progress = $progresses->getProgressForActivityOrModuleForLearner(
                $learner,
                $course,
                $module,
                $activity
            );

            $started = !empty($progress);
            $finished = $started && !empty($progresses->getUsernamesForFinishedActivity($course, $module, $activity));

            // TODO maybe adapt the spec here (it would be clearer if we don't mixed elapsed_time and duration in the same column)
            if ($this->wiki->config['lms_config']['use_only_custom_elapsed_time'] || !empty($progress['elapsed_time'])) {
                $activityDuration = !empty($progress['elapsed_time']) ?
                    $this->dateManager->createIntervalFromString($progress['elapsed_time'])
                    : null;
            } else {
                $activityDuration = $this->dateManager->createIntervalFromMinutes($activity->getDuration());
            }

            $firstAccessDate = !empty($progress['log_time']) ?
                $this->dateManager->createDateFromString($progress['log_time'])
                : null;

            $activitiesStat[$activity->getTag()] = [
                "started" => $started, // bool
                "finished" => $finished, //bool
                "elapsedTime" => $activityDuration, // CarbonInterval object,
                "firstAccessDate" => $firstAccessDate //Carbon object,
            ];
        }
        return $activitiesStat;
    }

    /**
     * Find first access date in a array of arrays with firstAccessDate
     * @param $stats array the array of arrays with 'firstAccessDate' key
     * @return Carbon | null the fist access date or null if no access date
     */
    private function findFirstAccessDate(array $stats): ?Carbon
    {
        $firstDateTime = null;
        foreach ($stats as $stat) {
            if (!$firstDateTime ||
                ($stat['firstAccessDate']
                    && $stat['firstAccessDate']->lessThan($firstDateTime))
            ) {
                $firstDateTime = $stat['firstAccessDate'];
            }
        }
        return $firstDateTime;
    }
}
