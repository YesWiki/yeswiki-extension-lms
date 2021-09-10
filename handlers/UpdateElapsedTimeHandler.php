<?php

use Carbon\CarbonInterval;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Service\LearnerDashboardManager;
use YesWiki\Lms\Course;
use YesWiki\Lms\Learner;
use YesWiki\Lms\Module;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\DateManager;
use YesWiki\Lms\Service\LearnerManager;

class UpdateElapsedTimeHandler extends YesWikiHandler
{
    protected $userManager;
    protected $courseManager;
    protected $learnerManager;
    protected $learnerDashboardManager;
    protected $dateManager;

    public function run()
    {
        $this->userManager = $this->getService(UserManager::class);
        $this->courseManager = $this->getService(CourseManager::class);
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
        // check validity for user (only for admins)
        $learnerName = null;
        if (isset($_GET['learner']) && $this->wiki->UserIsAdmin()) {
            $learnerName = isset($_GET['learner']) ? $_GET['learner'] : null;
            if (empty($this->userManager->getOneByName($learnerName))) {
                return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                    'type' => 'danger',
                    'message' => "In params, the user '$learnerName' does not exist"
                ]);
            }
        }
        // get learner (if null, set elapsed time for current learner)
        $learner = $this->learnerManager->getLearner($learnerName);
        // get course
        $courseTag = $_GET['course'];
        if (!empty($courseTag)) {
            $course = $this->courseManager->getCourse($courseTag);
            if (!$course) {
                return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                    'type' => 'danger',
                    'message' => "In params, the course '$courseTag' is not a course"
                ]);
            }
        } else {
            return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                'type' => 'danger',
                'message' => 'The parameter \'course\' is required'
            ]);
        }
        // get module
        $moduleTag = $_GET['module'];
        if (!empty($moduleTag)) {
            $module = $this->courseManager->getModule($moduleTag);
            if (!$module) {
                return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                    'type' => 'danger',
                    'message' => "In params, the module '$moduleTag' is not a module"
                ]);
            }
            if (!$course->hasModule($moduleTag)) {
                return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                    'type' => 'danger',
                    'message' => "In params, the module '$moduleTag' is a module but not a module of course '$courseTag'"
                ]);
            }
        } else {
            return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                'type' => 'danger',
                'message' => 'The parameter \'module\' is required'
                ]);
        }
        // get activity
        $activityTag = $_GET['activity'] ?? null;
        if (!empty($activityTag)) {
            $activity = $this->courseManager->getActivity($activityTag);
            if (!$activity) {
                return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                    'type' => 'danger',
                    'message' => "In params, the activity '$activityTag' is not an activity"
                ]);
            }
            if (!$module->hasActivity($activityTag)) {
                return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                    'type' => 'danger',
                    'message' => "In params, the activity '$activityTag' is an activity but not an activity of module '$moduleTag'"
                ]);
                return $this->renderErrorMsg();
            }
        } else {
            $activity = null;
        }
        // check if the needed parameters are defined
        if (!$learner || !$course || !$module) {
            return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                'type' => 'danger',
                'message' => 'The GET parameters \'learner\', \'course\' and \'module\' need to be defined'
                ]);
        }

        if (isset($_POST['elapsedtime'])) {
            // update mode
            return $this->renderUpdate($learner, $course, $module, $activity);
        }
        // render form to ask new value
        return $this->renderForm($learner, $course, $module, $activity);
    }

    private function renderUpdate(Learner $learner, Course $course, Module $module, ?Activity $activity): string
    {
        // get elapsedtime
        if (isset($_POST['elapsedtime'])) {
            if (!ctype_digit($_POST['elapsedtime']) && (intval($_POST['elapsedtime']) != 0)) {
                return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                    'type' => 'danger',
                    'message' => 'The GET parameter \'elapsedtime\' must be a positive integer'
                ]);
            }
            $elapsedTime = $this->dateManager->createIntervalFromMinutes(intval($_POST['elapsedtime']));
        } else {
            return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                'type' => 'danger',
                'message' => 'The parameter \'elapsedtime\' is required'
                ]);
        }
        // update value
        if ($elapsedTime->totalMinutes == 0) {
            // reset elapsed time
            $updateResult = $this->learnerManager->resetElapsedTimeForLearner($learner, $course, $module, $activity);
        } else {
            $updateResult = $this->learnerManager->saveElapsedTimeForLearner($learner, $course, $module, $activity, $elapsedTime);
        }
        if (!$updateResult) {
            return $this->twig->renderInSquelette('@templates/alert-message-with-back.twig', [
                'type' => 'danger',
                'message' => 'The update of elapsed time for course \'' . $course->getTag() .
                    '\', module \'' . $module->getTag() . '\', ' . (!empty($activity) ? 'activity \'' . $activity->getTag() . '\'' : '') .
                    ' and elapsed time \'' . $elapsedTime . '\' minutes has not worked !'
                ]);
        }
        // redirect to page
        $this->wiki->Redirect($this->wiki->Href('', '', $this->extractPreviousParams(), false));
    }

    private function renderForm(Learner $learner, Course $course, Module $module, ?Activity $activity): string
    {
        // get all courses
        $courses = $this->courseManager->getAllCourses();
        $coursesStat = $this->learnerDashboardManager->processCoursesStat($courses, $learner);

        $output = $this->wiki->header();
        $previousparams = $this->extractPreviousParams();
        $output .= $this->render('@lms/learner-update-elapsed-time.twig', [
            'learner' => $learner,
            'course' => $course,
            'module' => $module,
            'activity' => $activity,
            'previousparams' => $previousparams,
            'tag' => $this->wiki->GetPageTag(),
            'coursesStat' => $coursesStat
        ]);
        $output .= $this->wiki->footer();
        return $output;
    }

    private function extractPreviousParams(): array
    {
        if (isset($_GET['previousparams'])) {
            $previousparams = explode(',', $_GET['previousparams']);
            if ($previousparams) {
                $params = [];
                foreach ($previousparams as $paramName) {
                    $params[$paramName] = $_GET[$paramName];
                }
                return $params;
            }
        }
        return [];
    }
}
