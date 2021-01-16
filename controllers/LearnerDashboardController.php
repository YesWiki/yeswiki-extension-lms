<?php

namespace YesWiki\Lms\Controller;

use Carbon\Carbon;
use YesWiki\Core\YesWikiController;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Core\Service\UserManager;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Learner;
use YesWiki\Lms\Module;
use YesWiki\Lms\Progresses;

class LearnerDashboardController extends YesWikiController
{
    protected $userManager;
    protected $courseManager;

    /**
     * LearnerDashboardController constructor
     * @param UserManager $userManager the injected UserManager instance
     * @param CourseManager $courseManager the injected CourseManager instance
     */
    public function __construct(
        UserManager $userManager,
        CourseManager $courseManager
    ) {
        $this->userManager = $userManager;
        $this->courseManager = $courseManager;
    }

    /* process stats for learner dashboard basing on array of Courses
     * @param $courses array of Course
     * @param $Learner used to found personal data
     * return array coursesStat = []
     *       "courseTag" => [
     *              ... ,
     *              "modulesStat" => ...
     *          ]
     *       ]
     */
    public function processCoursesStat(array $courses, Learner $learner): ?array
    {
        $coursesStat = [] ;
        foreach ($courses as $course) {
            $modulesStat = $this->processModulesStat($course, $learner) ;

            $started = (count(array_filter($modulesStat, function ($moduleStat) {
                return $moduleStat['started'] ;
            })) > 0) ;

            $nbModulesFinished = count(array_filter($modulesStat, function ($moduleStat) {
                return $moduleStat['finished'] ;
            })) ;
            $nbModules = count($modulesStat) ;

            $finished = ($nbModulesFinished == $nbModules) ;

            $progressRatio = ($nbModules > 0) ? round($nbModulesFinished/$nbModules * 100) : 0 ;

            $coursesStat[$course->getTag()] = [
                "started" => $started , // bool
                "finished" => $finished , //bool
                "progressRatio" => $progressRatio , // int between 0 and 100 in pourcent
                "elapsedTime" => null ,//DateInterval object,
                "firstAccessDate" => $this->findFirstAccessDate($modulesStat) ,//Carbon object,
                "modulesStat" => $modulesStat
            ];
        }
        return $coursesStat ;
    }

    /* process modules stats for learner dashboard basing on one course
     * @param $course course
     * @param $Learner used to found personal data
     * return array modulesStat = []
     *       "moduleTag" => [
     *              ... ,
     *              "activitiesStat" => ...
     *          ]
     *       ]
     */
    private function processModulesStat(Course $course, Learner $learner): ?array
    {
        $modulesStat = [] ;
        $modules = $course->getModules() ;
        $progresses = $learner->getAllProgresses() ;
        foreach ($modules as $module) {
            $activitiesStat = $this->processActivitiesStat($course, $module, $learner , $progresses) ;
            // get progress
            $progress = $progresses->getProgressForActivityOrModuleForLearner(
                $learner,
                $course,
                $module,
                null
            ) ;

            $started = ($progress) || (count(array_filter($activitiesStat, function ($activityStat) {
                return $activityStat['started'] ;
            })) > 0) ;

            $nbActivitiesFinished = count(array_filter($activitiesStat, function ($activityStat) {
                return $activityStat['finished'] ;
            })) ;
            $nbActivities = count($activitiesStat) ;

            $finished = ($nbActivitiesFinished == $nbActivities) ;

            $progressRatio = ($nbActivities > 0) ? round($nbActivitiesFinished/$nbActivities * 100) : 0 ;

            $firstAccessDate = $this->accessDate($progress) ;
            $firstActivityAccessDate = $this->findFirstAccessDate($activitiesStat) ;
            if ($firstAccessDate && $firstActivityAccessDate && $firstAccessDate->diff($firstActivityAccessDate, true) === false) {
                $firstAccessDate = $firstActivityAccessDate ;
            } elseif (empty($firstAccessDate) && $firstActivityAccessDate) {
                $firstAccessDate = $firstActivityAccessDate;
            }

            $modulesStat[$module->getTag()] = [
                "started" => $started , // bool
                "finished" => $finished , //bool
                "progressRatio" => $progressRatio , // int between 0 and 100 in pourcent
                "elapsedTime" => null ,//DateInterval object,
                "firstAccessDate" => $firstAccessDate ,//Carbon object,
                "activitiesStat" => $activitiesStat
            ];
        }
        return $modulesStat ;
    }

    /* process activities stats for learner dashboard basing on one module
     * @param $course course
     * @param $module module
     * @param $learner Learnerresses used to found personal data
     * @param $progresses Progresses used to found personal data
     * return array activitiesStat = []
     *       "activityTag" => [
     *              ...
     *          ]
     *       ]
     */
    private function processActivitiesStat(Course $course, Module $module, Learner $learner, Progresses $progresses): ?array
    {
        $activitiesStat = [] ;
        $activities = $module->getActivities() ;
        foreach ($activities as $activity) {
            // get progress
            $progress = $progresses->getProgressForActivityOrModuleForLearner(
                $learner,
                $course,
                $module,
                $activity
            ) ;

            $started = ($progress) ;

            if ($started) {
                $finished = (count($progresses->getUsernamesForFinishedActivity($course, $module, $activity)) > 0) ;
            } else {
                $finished = false ;
            }

            $activitiesStat[$activity->getTag()] = [
                "started" => $started , // bool
                "finished" => $finished , //bool
                "elapsedTime" => null ,//DateInterval object,
                "firstAccessDate" =>  $this->accessDate($progress) //Carbon object,
            ];
        }
        return $activitiesStat ;
    }

    /* Find first access date in a array of arrays with firstAccessDate
     * @param $stats array of arrays with 'firstAccessDate' key
     * return Carbon
     */
    private function findFirstAccessDate(array $stats): ?Carbon
    {
        if (count($stats) > 0) {
            $fisrtDateTime = null ;
            foreach ($stats as $stat) {
                if (!$fisrtDateTime ||
                        ($stat['firstAccessDate'] &&
                        $fisrtDateTime->diff($stat['firstAccessDate'], true) === false)) {
                    $fisrtDateTime = $stat['firstAccessDate'] ;
                }
            }
            return $fisrtDateTime;
        } else {
            return null ;
        }
    }
    
    /* Access date from progree
     * @param $progress array values with 'log_time' key
     * return Carbon
     */
    private function accessDate(?array $progress): ?Carbon
    {
        $firstAccessDate = ($progress && $progress['log_time']) ? 
            new Carbon(\DateTime::createFromFormat('Y-m-d H:i:s', $progress['log_time'])) : 
            null ;
        $firstAccessDate = ($firstAccessDate) ? $firstAccessDate->locale($GLOBALS['prefered_language']): null ;
        return $firstAccessDate ;
    }
}
