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
        // user connected ?
        if ($this->userManager->getLoggedUser() == '') {
            // not connected
            return $this->renderErrorMSG(_t('LMS_LOGGED_USERS_ONLY_HANDLER') . ' ExportDashboardCSV') ;
        }
        // get user name option only for admins
        if ($this->LearnerDashboardController->UserIsAdvanced()) {
            // get user name option
            $learnerNameOption = $this->wiki->GetParameter('learner');
            $learnerNameOption = (empty($learnerNameOption)) ? ((empty($_REQUEST['learner'])) ? '' : $_REQUEST['learner']) : $learnerNameOption ;
        } else {
            $learnerNameOption = '' ;
        }
        // get learner
        $this->learner = $this->learnerManager->getLearner($learnerNameOption);
        if (!$this->learner) {
            // not connected
            return $this->renderErrorMSG(_t('LMS_LOGGED_USERS_ONLY_HANDLER') . ' ExportDashboardCSV') ;
        }
        return $this->exportToCSV() ;
    }

    

    public function exportToCSV(): string
    {
        $courseTag = (isset($_GET['course'])) ? $_GET['course'] : null ;
        $courseTag = (!$courseTag && isset($_POST['course'])) ? $_POST['course'] : $courseTag ;

        if ($courseTag) {
            // get one tag
            $courses = $this->courseManager->getCourse($courseTag) ;
            $courses = ($courses) ? [$courses] : null ;
        }
        if (!isset($courses)) {
            // get all courses
            $courses = $this->courseManager->getAllCourses() ;
        }
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
            $elapsedTime = ($courseStat['elapsedTime']) ?
                $courseStat['elapsedTime']->format('%h h %I min.') : null ;
            $row = [
                _t('LMS_DASHBOARD_COURSE') . ' '. $courseIndex,
                $course->getTitle(),
                $progressRatio,
                $elapsedTime /* TODO elapsedTime */,
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
                $elapsedTime = ($moduleStat['elapsedTime'] &&
                                !($this->wiki->config['lms_config']['use_only_custom_elapsed_time'] && !$moduleStat['finished'])) ?
                                $moduleStat['elapsedTime']->format('%h h %I min.') : null ;
                $row = [
                    _t('LMS_DASHBOARD_MODULE') . ' '. $courseIndex . '.' . $moduleIndex ,
                    $module->getTitle(),
                    $progressRatio,
                    $elapsedTime,
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
                    $elapsedTime = ($this->wiki->config['lms_config']['display_activity_elapsed_time'] &&
                                        $activityStat['elapsedTime'] && $activityStat['finished']) ?
                                    $activityStat['elapsedTime']->format('%h h %I min.') : null ;
                    $row = [
                        _t('LMS_ACTIVITY') . ' '. $courseIndex  . '.' . $moduleIndex . '.' . $activityIndex,
                        $activity->getTitle(),
                        $progressRatio,
                        $elapsedTime,
                        ($activityStat['firstAccessDate']) ? $activityStat['firstAccessDate']->isoFormat('LLLL') : ''
                    ];
                    fputcsv($output, $row) ;
                }
            }
        }

        return '' ;
    }

    private function renderErrorMSG(string $errorMessage): string
    {
        $output = $this->wiki->header() ;
        $output .= $this->render('@lms/alert-message.twig', [
                'alertMessage' => $errorMessage
            ]);
        $output .= $this->render('@lms/return-button.twig', [
                'tag' => $this->wiki->GetPageTag()
            ]);
        $output .= $this->wiki->footer() ;
        return $output ;
    }
}
