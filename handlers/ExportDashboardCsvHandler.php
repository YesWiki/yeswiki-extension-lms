<?php

use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\DateManager;
use YesWiki\Lms\Service\LearnerDashboardManager;
use YesWiki\Lms\Service\LearnerManager;

class ExportDashboardCsvHandler extends YesWikiHandler
{
    protected $courseManager;
    protected $userManager;
    protected $learnerManager;
    protected $learnerDashboardManager;
    protected $dateManager;

    protected $learner;

    public function run()
    {
        $this->courseManager = $this->getService(CourseManager::class);
        $this->userManager = $this->getService(UserManager::class);
        $this->learnerManager = $this->getService(LearnerManager::class);
        $this->learnerDashboardManager = $this->getService(LearnerDashboardManager::class);
        $this->dateManager = $this->getService(DateManager::class);

        // user connected ?
        if (!$this->userManager->getLoggedUser()) {
            // not connected
            return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                'type' => 'danger',
                'message' => _t('LMS_LOGGED_USERS_ONLY_HANDLER') . ' (exportdashboardcsv)'
            ]);
        }
        // get user name option only for admins
        if ($this->wiki->UserIsAdmin()) {
            // get the learner parameter only from GET
            $learnerNameOption = !empty($_GET['learner']) ? $_GET['learner'] : null;
        }
        // get learner
        $this->learner = $this->learnerManager->getLearner($learnerNameOption ?? null);
        if (!$this->learner) {
            // not connected
            return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                'type' => 'danger',
                'message' => _t('LMS_LOGGED_USERS_ONLY_HANDLER') . ' (exportdashboardcsv)'
            ]);
        }
        // get course
        $courseTag = !empty($_GET['course']) ? $_GET['course'] : null;
        if ($courseTag) {
            // restrict to only one course
            $course = $this->courseManager->getCourse($courseTag);
            $courses = $course ? [$course] : null;
        }
        if (!isset($courses)) {
            // get all courses
            $courses = $this->courseManager->getAllCourses();
        }

        $this->exportToCSV($courses);
    }

    public function exportToCSV(array $courses)
    {
        $coursesStat = $this->learnerDashboardManager->processCoursesStat($courses, $this->learner);

        // output headers so that the file is downloaded rather than displayed
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $this->learner->getUsername() . '_' . _t('LMS_DASHBOARD_FILENAME') . '.csv');

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
        $courseIndex = 0;
        foreach ($courses as $course) {
            $courseIndex += 1;
            $courseStat = $coursesStat[$course->getTag()];
            if (empty($courseStat['progressRatio'])) {
                $progressRatio = ($courseStat['started']) ? _t('LMS_DASHBOARD_IN_COURSE') : '----';
            } else {
                $progressRatio = $courseStat['progressRatio'] . ' %';
            }
            $elapsedTime = ($courseStat['elapsedTime']) ?
                $this->dateManager->formatTimeWithColons($courseStat['elapsedTime']) : null;
            $row = [
                _t('LMS_DASHBOARD_COURSE') . ' ' . $courseIndex,
                $course->getTitle(),
                $progressRatio,
                $elapsedTime /* TODO elapsedTime */,
                $courseStat['firstAccessDate'] ?
                    $this->dateManager->formatLongDatetime($courseStat['firstAccessDate'])
                    : null
            ];
            fputcsv($output, $row);
            $moduleIndex = 0;
            foreach ($course->getModules() as $module) {
                $moduleIndex += 1;
                $moduleStat = $courseStat['modulesStat'][$module->getTag()];
                if (empty($moduleStat['progressRatio'])) {
                    $progressRatio = ($moduleStat['started']) ? _t('LMS_DASHBOARD_IN_COURSE') : '----';
                } else {
                    $progressRatio = $moduleStat['progressRatio'] . ' %';
                }
                $elapsedTime = ($moduleStat['elapsedTime']
                    && !($this->wiki->config['lms_config']['use_only_custom_elapsed_time'] && !$moduleStat['finished'])
                ) ?
                    $this->dateManager->formatTimeWithColons($moduleStat['elapsedTime'])
                    : null;
                $row = [
                    _t('LMS_DASHBOARD_MODULE') . ' ' . $courseIndex . '.' . $moduleIndex,
                    $module->getTitle(),
                    $progressRatio,
                    $elapsedTime,
                    $moduleStat['firstAccessDate'] ?
                        $this->dateManager->formatLongDatetime($moduleStat['firstAccessDate'])
                        : null
                ];
                fputcsv($output, $row);
                $activityIndex = 0;
                foreach ($module->getActivities() as $activityIndex => $activity) {
                    $activityIndex += 1;
                    $activityStat = $moduleStat['activitiesStat'][$activity->getTag()];
                    if ($activityStat['started']) {
                        $progressRatio = ($activityStat['finished']) ? _t('LMS_DASHBOARD_FINISHED_F') : _t('LMS_DASHBOARD_IN_COURSE');
                    } else {
                        $progressRatio = '----';
                    }
                    $elapsedTime = ($this->wiki->config['lms_config']['display_activity_elapsed_time']
                        && $activityStat['elapsedTime'] && $activityStat['finished']
                    ) ?
                        $this->dateManager->formatTimeWithColons($activityStat['elapsedTime'])
                        : null;
                    $row = [
                        _t('LMS_ACTIVITY') . ' ' . $courseIndex . '.' . $moduleIndex . '.' . $activityIndex,
                        $activity->getTitle(),
                        $progressRatio,
                        $elapsedTime,
                        $activityStat['firstAccessDate'] ?
                            $this->dateManager->formatLongDatetime($activityStat['firstAccessDate'])
                            : null
                    ];
                    fputcsv($output, $row);
                }
            }
        }
    }
}
