<?php

use YesWiki\Core\YesWikiHandler;
use YesWiki\Lms\Controller\LearnerDashboardController;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Core\Service\UserManager;
use YesWiki\Lms\Learner;

class ExportDashboardCSvHandler extends YesWikiHandler
{
    protected $courseManager ;
    protected $userManager;
    protected $learnerManager ;
    protected $LearnerDashboardController ;
    protected $learner ;

    public function run()
    {
        $this->courseManager = $this->getService(CourseManager::class);
        $this->learnerManager = $this->getService(LearnerManager::class);
        $this->LearnerDashboardController = $this->getService(LearnerDashboardController::class);
        $this->userManager = $this->getService(UserManager::class);
        // get user name option
        $userNameOption = $this->wiki->GetParameter('user');
        $userNameOption = (empty($userNameOption)) ? ((empty($_REQUEST['user'])) ? '' : $_REQUEST['user']) : $userNameOption ;
        // get learner
        $this->learner = $this->learnerManager->getLearner($userNameOption);
        if (!$this->learner) {
            // not connected
            return $this->render('@lms/alert-message.twig', [
                'alertMessage' => _t('LOGGED_USERS_ONLY_ACTION') . ' “learnerdashboard”'
            ]);
        }
        return $this->exportToCSV() ;
    }

    

    public function exportToCSV(): string
    {
        // get all courses
        $courses = $this->courseManager->getAllCourses() ;
        
        $coursesStat = $this->LearnerDashboardController->processCoursesStat($courses, $this->learner) ;

        // output headers so that the file is downloaded rather than displayed
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename='. $this->learner->getUsername() .'_'. _t('LMS_DASHBOARD_FILENAME') .'.csv');

        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');

        // output the column headings
        fputcsv($output, [
            _t('LMS_DASHBOARD_TYPE'),
            _t('LMS_DASHBOARD_NAME'),
            _t('LMS_DASHBOARD_PROGRESS'),
            _t('LMS_DASHBOARD_ELAPSEDTIME'),
            _t('LMS_DASHBOARD_FIRSTACCESS')
            ]);

        // loop over the courses, outputting them
        $courseIndex = 0 ;
        foreach ($courses as $course) {
            $courseIndex += 1 ;
            $courseStat = $coursesStat[$course->getTag()] ;
            if (empty($courseStat['progressRatio'])) {
                $progressRatio = ($courseStat['started']) ? _t('LMS_DASHBOARD_IN_COURSE') : '----' ;
            } else {
                $progressRatio = $courseStat['progressRatio'] . ' %';
            }
            $row = [
                _t('LMS_DASHBOARD_COURSE') . ' '. $courseIndex,
                $course->getTitle(),
                $progressRatio,
                '' /* TODO elapsedTime */,
                ($courseStat['firstAccessDate']) ? $courseStat['firstAccessDate']->isoFormat('LLLL') : '' 
            ];
            fputcsv($output, $row) ;
            $moduleIndex = 0 ;
            foreach ($course->getModules() as $module) {
                $moduleIndex += 1 ;
                $moduleStat = $courseStat['modulesStat'][$module->getTag()] ;
                if (empty($moduleStat['progressRatio'])) {
                    $progressRatio = ($moduleStat['started']) ? _t('LMS_DASHBOARD_IN_COURSE') : '----' ;
                } else {
                    $progressRatio = $moduleStat['progressRatio'] . ' %';
                }
                $row = [
                    _t('LMS_DASHBOARD_MODULE') . ' '. $courseIndex . '.' . $moduleIndex ,
                    $module->getTitle(),
                    $progressRatio,
                    '' /* TODO elapsedTime */,
                    ($moduleStat['firstAccessDate']) ? $moduleStat['firstAccessDate']->isoFormat('LLLL') : '' 
                ];
                fputcsv($output, $row) ;
                $activityIndex = 0 ;
                foreach ($module->getActivities() as $activityIndex => $activity) {
                    $activityIndex += 1 ;
                    $activityStat = $moduleStat['activitiesStat'][$activity->getTag()] ;
                    if ($activityStat['started']) {
                        $progressRatio = ($activityStat['finished']) ? _t('LMS_DASHBOARD_FINISHED_F') : _t('LMS_DASHBOARD_IN_COURSE') ;
                    } else {
                        $progressRatio = '----';
                    }
                    $row = [
                        _t('LMS_ACTIVITY') . ' '. $courseIndex  . '.' . $moduleIndex . '.' . $activityIndex,
                        $activity->getTitle(),
                        $progressRatio,
                        '' /* TODO elapsedTime */,
                        ($activityStat['firstAccessDate']) ? $activityStat['firstAccessDate']->isoFormat('LLLL') : '' 
                    ];
                    fputcsv($output, $row) ;
                }
            }
        }

        return '' ;
    }
}
