<?php

use YesWiki\Core\YesWikiAction;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Learner;
use YesWiki\Lms\Module;

class LearnerDashBoardAction extends YesWikiAction
{
    protected $courseManager ;
    protected $learner ;
    
    public function run()
    {
        $this->courseManager = $this->getService(CourseManager::class);
        // get learner
        $this->learner = $this->courseManager->getLearner();
        if (!$this->learner) {
            // not connected
            return $this->render('@lms/alert-message.twig', [
                'alertMessage' => _t('LOGGED_USERS_ONLY_ACTION') . ' “learnerdashboard”'
            ]);
        }
        // get all courses
        $courses = $this->courseManager->getAllCourses() ;
        
        $preparedCourses = $this->prepareCourses($courses) ;
        
        // $menuUserService = $this->getService(MenuUserService::class);
        return $this->render('@lms/LearnerDashBoard.twig', [
            'userName' => $this->learner->getUserName(),
            'courses' => $preparedCourses]);
    }

    private function prepareCourses(array $courses): ?array
    {
        $preparedCourses = [] ;
        foreach ($courses as $course) {
            // get progress
            $preparedModules = $this->prepareModules($course) ;
            // check if modules are started
            $inCourse = (count(array_filter($preparedModules,function ($preparedModule) {
                return ($preparedModule['progress'] != _t('LMS_DASHBOARD_FINISHED')
                    || $preparedModule['progress'] != '') ;
                })) > 0 ) ;
            // check if modules are finished
            $finished = (count(array_filter($preparedModules,function ($preparedModule) {
                  return ($preparedModule['progress'] != _t('LMS_DASHBOARD_FINISHED')) ;
                })) == 0 ) ;
            if ($inCourse || $finished) {
                $progressText = ($finished) ? _t('LMS_DASHBOARD_FINISHED') : _t('LMS_DASHBOARD_IN_COURSE') ;
            } else {
                $progressText = '' ;
            }
            if ($inCourse && !$finished) {
                $partialDuration = $this->getPartialDuration($preparedModules) ;
                $totalDuration = $this->getPartialDuration($preparedModules,true) ;
                $rateTime = ($totalDuration > 0) ? floor($partialDuration / $totalDuration*100) : 0;
                $progressText = ($rateTime > 0) ? $rateTime . ' %'  : $progressText ;
            } else {
                $partialDuration = 0 ;
            }
    
            $preparedCourses[] = [
                    'name' => $course->getField('bf_titre'),
                    'id' => $course->getTag() ,
                    'progress' => $progressText,
                    'elapsedTime' => ($finished) ? $this->convertToStr($this->getPartialDuration($preparedModules,true)) :  $this->convertToStr($partialDuration),
                    'lastActivity' => $this->lastActivityDateForModuleOrActivity($preparedModules),
                    'modules' => $preparedModules
                ] ;
        }
        return $preparedCourses ;
    }

    private function prepareModules(Course $course): ?array
    {
        $preparedModules = [] ;
        $modules = $course->getModules() ;
        foreach ($modules as $module) {
            // get progress
            $progress = $this->learner->getProgress($course,$module,null) ;
            // prepare activities
            $preparedActivities = $this->prepareActivities($course,$module) ;
            $inCourse = ($progress) ;
            $finished = false ;
            if (!$inCourse) {
                // check if activities are started
                $inCourse = (count(array_filter($preparedActivities,function ($preparedActivity) {
                        return ($preparedActivity['progress'] == _t('LMS_DASHBOARD_IN_COURSE')) ;
                    })) > 0 ) ;
            }
            // check if activities are finished
            $finished = (count(array_filter($preparedActivities,function ($preparedActivity) {
                    return ($preparedActivity['progress'] != _t('LMS_DASHBOARD_FINISHED_F')) ;
                })) == 0 ) ;

            if ($inCourse || $finished) {
                $progressText = ($finished) ? _t('LMS_DASHBOARD_FINISHED') : _t('LMS_DASHBOARD_IN_COURSE') ;
            } else {
                $progressText = '' ;
            }
            if ($inCourse && !$finished) {
                $partialDuration = $this->getPartialDuration($preparedActivities) ;
                $totalDuration = $this->getPartialDuration($preparedActivities,true) ;
                $rateTime = ($totalDuration > 0) ? floor($partialDuration / $totalDuration*100) : 0;
                $progressText = ($rateTime > 0) ? $rateTime . ' %'  : $progressText ;
            } else {
                $partialDuration = 0 ;
            }

            $preparedModules[] = [
                    'name' => $module->getField('bf_titre'),
                    'id' => $module->getTag() ,
                    'progress' => $progressText,
                    'elapsedTime' => ($finished) ? $module->getDuration() :  $this->convertToStr($partialDuration),
                    'lastActivity' => $this->lastActivityDateForModuleOrActivity($preparedActivities) ,
                    'activities' => $preparedActivities,
                    'estimatedDuration' => $module->getDuration()
                ] ;
        }
        return $preparedModules ;
    }

    private function prepareActivities(Course $course, Module $module): ?array
    {
        $preparedActivities = [] ;
        $activities = $module->getActivities() ;
        foreach ($activities as $activity) {
            // get progress
            $progress = $this->learner->getProgress($course,$module,$activity) ;
            if ($progress) {
                // check the next activity
                if ($module->getLastActivityTag() != $activity->getTag()) {
                    // get next activity progress
                    $nextActivityProgress = $this->learner->getProgress($course,$module,$module->getNextActivity($activity->getTag())) ;
                    $finished = ($nextActivityProgress) ;
                } else {
                    // check next module
                    $nextModule = $course->getNextModule($module->getTag()) ;
                    $nextModuleProgress = $this->learner->getProgress($course,$nextModule,null) ;
                    $firstActivityofNextModuleProgress = $this->learner->getProgress($course,$nextModule,
                        $nextModule->getActivities()[0]);
                    $finished = ($nextModuleProgress || $firstActivityofNextModuleProgress) ;
                }
                $progressText = ($finished) ? _t('LMS_DASHBOARD_FINISHED_F') : _t('LMS_DASHBOARD_IN_COURSE') ;
            } else {
                $progressText = '' ;
                $finished = false ;
            }
            $preparedActivities[] = [
                    'name' => $activity->getField('bf_titre'),
                    'id' => $activity->getTag() ,
                    'progress' => $progressText,
                    'elapsedTime' => ($finished) ? $activity->getEstimatedDuration() . ' min': '',
                    'lastActivity' => $this->lastActivityTime($progress) ,
                    'estimatedDuration' => $activity->getEstimatedDuration() . ' min' 
                ] ;
        }
        return $preparedActivities ;
    }

    private function lastActivityTime(?Array $progress): string
    {
        if ($progress) {
            $lastTime = 0 ;
            foreach($progress as $value) {
                $lastTime = ($value['time'] && $value['time'] > $lastTime) ? $value['time'] : $lastTime ;
            }
            return ($lastTime > 0) ? date('j M Y',$lastTime) : '' ;
        } else {
            return '' ;
        }
    }

    private function lastActivityDateForModuleOrActivity(Array $preparedStructures): string
    {
        if (count ($preparedStructures) > 0) {
            $lastTime = 0 ;
            $lastDate = '' ;
            foreach($preparedStructures as $preparedStructure) {
                if ($preparedStructure['lastActivity'] && strtotime($preparedStructure['lastActivity']) > $lastTime) {
                    $lastDate = $preparedStructure['lastActivity'] ;
                    $lastTime = strtotime($lastDate) ;
                }
            }
            return ($lastTime > 0) ? $lastDate : '' ;
        } else {
            return '' ;
        }
    }

    private function getPartialDuration(Array $preparedActivities, bool $getTotal = false): int
    {
        if (count ($preparedActivities) > 0) {
            $time = 0; // in minutes
            foreach ($preparedActivities as $activity) {
                if ($activity['elapsedTime'] != '') {
                    $time += $this->extractTime($activity['elapsedTime']) ;
                } else if ($getTotal) {
                    $time += $this->extractTime($activity['estimatedDuration']) ;
                }
            }
            return $time ;
        } else {
            return 0 ;
        }
    }

    private function extractTime(string $time): int
    {
        if (strpos($time,'h') === false) {
            $hours = 0 ;
            $minutes = explode(' min',$time)[0] ;
        } else {
            list($hours,$minutes) =  explode('h',$time) ;
            $hours = (empty(intval($hours))) ? 0 : intval($hours) ;
        }
        $minutes = (empty(intval($minutes))) ? 0 : intval($minutes) ;
        return $hours * 60 + $minutes ;
    }

    private function convertToStr(int $duration): string
    {
        if ($duration > 0) {
            $hours = floor($duration / 60);
            $minutes = ($duration % 60);
            return sprintf('%dh%02d', $hours, $minutes);
        } else {
            return '' ;
        }
    }
}
