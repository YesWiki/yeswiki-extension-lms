<?php

use YesWiki\Bazar\Service\BazarListService;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\YesWikiAction;
use YesWiki\Lms\Controller\CourseController;
use YesWiki\Lms\Course;
use YesWiki\Lms\Module;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\ExtraActivityManager;
use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Lms\Service\QuizManager;

class ProgressDashboardAction extends YesWikiAction
{
    protected $courseController;
    protected $courseManager;
    protected $learnerManager;
    protected $entryManager;
    protected $formManager;
    protected $extraActivityManager;
    protected $quizManager;

    // the progresses related to the current course for all users
    protected $progresses;
    // the learners which have already a progress in the current course
    protected $learners;

    // $activitiesStat, $moduleStat & $coursesStat are array with the same structure :
    //  [
    //      tag =>
    //          [
    //              ['finished' => [username1,  ... userNameN],
    //              ['notFinished' => [userEntry1, ... userEntryN]
    //          ],
    //      ...
    //      tagN =>
    //          [
    //              ...
    //          ]
    //  ]
    protected $activitiesStat = [];
    // $modulesStat have only one value when we render the module progress dashboard
    protected $modulesStat = [];
    // we keep also the same structure for $courseStat even if it has always one value
    protected $coursesStat = [];

    public function run()
    {
        $this->courseController = $this->getService(CourseController::class);
        $this->courseManager = $this->getService(CourseManager::class);
        $this->learnerManager = $this->getService(LearnerManager::class);
        $this->entryManager = $this->getService(EntryManager::class);
        $this->formManager = $this->getService(FormManager::class);
        $this->extraActivityManager = $this->getService(ExtraActivityManager::class);
        $this->quizManager = $this->getService(QuizManager::class);

        $currentLearner = $this->learnerManager->getLearner();
        if (!$currentLearner || !$currentLearner->isAdmin()) {
            // reserved only to the admins
            return $this->render("@templates/alert-message.twig", [
                'type' => 'danger',
                'message' => _t('ACLS_RESERVED_FOR_ADMINS') . ' (progressdashboard)'
            ]);
        }

        // the course for which we want to display the dashboard
        $course = $this->courseController->getContextualCourse();
        if (!$course) {
            // information msg if no course available
            return $this->render("@templates/alert-message.twig", [
                'type' => 'info',
                'message' => _t('LMS_MISSING_COURSE') . ' ' . '<a href="' . $this->wiki->href('', 'BazaR') .
                    '">' . _t('LMS_MISSING_COURSE_PAGELINK') . '.'
            ]);
        }

        // switch to extra activity if needed
        if ($this->wiki->config['lms_config']['extra_activity_enabled']
            && isset($_REQUEST['extra_activity_mode'])
            && $message = $this->callAction('extraactivity', $this->arguments)
        ) {
            return $message;
        }

        // the progresses we are going to process
        $this->progresses = $this->learnerManager->getProgressesForAllLearners($course);
        // the learners for this course, we count all users which have already a progress
        $this->setLearnersFromUsernames($this->progresses->getAllUsernames());

        // check if a GET module parameter is defined
        $moduleParam = isset($_GET['module']) ? $_GET['module'] : null;

        if ($moduleParam) {
            $module = $this->courseManager->getModule($moduleParam);
            return $this->renderModuleProgressDashboard($module, $course);
        } else {
            return $this->renderCourseProgressDashboard($course);
        }
    }

    private function renderModuleProgressDashboard($module, $course): string
    {
        if (!$module || !$course->hasModule($module->getTag())) {
            return $this->render("@templates/alert-message.twig", [
                'type' => 'danger',
                'message' => _t('LMS_ERROR_NOT_A_VALID_MODULE') . ' (progressdashboard)'
            ]);
        }

        $this->processActivitiesAndModuleStat($course, $module);

        // render the dashboard for a module
        return $this->render('@lms/progress-dashboard-module.twig', [
            'course' => $course,
            'module' => $module,
            'activitiesStat' => $this->activitiesStat,
            'modulesStat' => $this->modulesStat,
            'extraActivityEnabled' => $this->wiki->config['lms_config']['extra_activity_enabled'],
            'learners' => $this->learners,
            'debug' => isset($_GET['debug']),
        ]);
    }

    private function renderCourseProgressDashboard($course): string
    {
        foreach ($course->getModules() as $module) {
            $this->processActivitiesAndModuleStat($course, $module);
        }
        $this->processCourseStat($course);

        // load filters if the learner_form_id corresponds to an existing form
        $learnerForm = $this->formManager->getOne($this->wiki->config['lms_config']['learner_form_id']);
        if ($learnerForm) {
            $bazarListService = $this->getService(BazarListService::class);
            $learnersEntries = array_map(function ($learner) {
                $userEntry = $learner->getUserEntry();
                return $userEntry ? $userEntry :
                    // id_typeannonce must be defined for facette filters
                    ['id_typeannonce' => $this->wiki->config['lms_config']['learner_form_id']];
            }, $this->learners);
            $forms = [$learnerForm];
            $filtersFieldnames = array_map('trim',
                explode(",", $this->wiki->config['lms_config']['progress_dashboard_filters']));

            $filtersArg = ["groups" => $filtersFieldnames, "groupsexpanded" => true];
            $filters = $bazarListService->formatFilters($filtersArg, $learnersEntries, $forms);
        }

        // render the dashboard for a course
        return $this->render('@lms/progress-dashboard-course.twig', [
            'course' => $course,
            'modulesStat' => $this->modulesStat,
            'courseStat' => $this->coursesStat,
            'extraActivityEnabled' => $this->wiki->config['lms_config']['extra_activity_enabled'],
            'learners' => $this->learners,
            'filters' => $filters ?? null,
            'facette' => $_GET['facette'] ?? null,
            'debug' => isset($_GET['debug']),
        ]);
    }

    private function processActivitiesStat(Course $course, Module $module)
    {
        foreach ($module->getActivities() as $activity) {
            $finishedUsernames = $this->progresses->getUsernamesForFinishedActivity($course, $module, $activity);

            // the users who havn't finished are those whose username is not in $finishedUsernames
            $notFinishedUsernames = array_diff(array_keys($this->learners), $finishedUsernames);
            ksort($finishedUsernames);
            ksort($notFinishedUsernames);

            $this->activitiesStat[$activity->getTag()] = [];
            $this->activitiesStat[$activity->getTag()]['finished'] = $finishedUsernames;
            $this->activitiesStat[$activity->getTag()]['notFinished'] = $notFinishedUsernames;
            if ($this->quizManager->hasQuizResults(null, $course->getTag(), $module->getTag(), $activity->getTag())) {
                $this->activitiesStat[$activity->getTag()]['hasQuizResults'] = true;
            }
        }
    }

    private function processActivitiesAndModuleStat(Course $course, Module $module)
    {
        $this->processActivitiesStat($course, $module);

        // for each module, we have to keep the users which have finished all activities of the module
        $finishedUsernames = [];
        foreach ($module->getActivities() as $activity) {
            if ($activity->getTag() == $module->getFirstActivityTag()) {
                // for the first activity, init with the usernames which have finished
                $finishedUsernames = $this->activitiesStat[$activity->getTag()]['finished'];
            } else {
                // each time, we keep only the usernames which have finished the current activity and all the previous ones
                $finishedUsernames = array_intersect(
                    $this->activitiesStat[$activity->getTag()]['finished'],
                    $finishedUsernames
                );
            }
        }

        if ($this->wiki->config['lms_config']['extra_activity_enabled']) {
            $module->setExtraActivityLogs($this->extraActivityManager->getExtraActivityLogs($course, $module));
        }
        // $finishedUsernames contains now the usernames which have finished the module
        $notFinishedUsernames = array_diff(array_keys($this->learners), $finishedUsernames);
        ksort($finishedUsernames);
        ksort($notFinishedUsernames);
        $this->modulesStat[$module->getTag()]['finished'] = $finishedUsernames;
        $this->modulesStat[$module->getTag()]['notFinished'] = $notFinishedUsernames;
        if ($this->quizManager->hasQuizResults(null, $course->getTag(), $module->getTag())) {
            $this->modulesStat[$module->getTag()]['hasQuizResults'] = true;
        }
    }

    private function processCourseStat(Course $course)
    {
        // we have to keep the users which have finished all modules of the course
        $finishedUsernames = [];
        foreach ($course->getModules() as $module) {
            if ($module->getTag() == $course->getFirstModuleTag()) {
                // for the first module, init with the usernames which have finished
                $finishedUsernames = $this->modulesStat[$module->getTag()]['finished'];
            } else {
                // each time, we keep only the usernames which have finished the current module and all the previous ones
                $finishedUsernames = array_intersect(
                    $this->modulesStat[$module->getTag()]['finished'],
                    $finishedUsernames
                );
            }
        }

        if ($this->wiki->config['lms_config']['extra_activity_enabled']) {
            $course->setExtraActivityLogs($this->extraActivityManager->getExtraActivityLogs($course));
        }
        // $finishedUsernames contains now the usernames which have finished the course
        $notFinishedUsernames = array_diff(array_keys($this->learners), $finishedUsernames);
        ksort($finishedUsernames);
        ksort($notFinishedUsernames);
        $this->coursesStat[$course->getTag()]['finished'] = $finishedUsernames;
        $this->coursesStat[$course->getTag()]['notFinished'] = $notFinishedUsernames;
    }

    /**
     * Set learners (with an associative array to get an easy access) from the array of username
     * @param $usernames the usernames for which we want to build the user entries array
     */
    private function setLearnersFromUsernames($usernames): void
    {
        $this->learners = [];
        foreach ($usernames as $username) {
            $learner = $this->learnerManager->getLearner($username);
            $this->learners[$username] = $learner;
        }
    }
}
