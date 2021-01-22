<?php

use YesWiki\Core\YesWikiHandler;
use YesWiki\Core\Service\UserManager;
use YesWiki\Lms\Controller\LearnerDashboardController;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Learner;
use YesWiki\Lms\Module;
use YesWiki\Wiki;

class UpdateElapsedTimeHandler extends YesWikiHandler
{
    protected $userManager;
    protected $courseManager;
    protected $learnerManager ;
    protected $LearnerDashboardController ;

    public function run()
    {
        $this->userManager = $this->getService(UserManager::class);
        $this->courseManager = $this->getService(CourseManager::class);
        $this->learnerManager = $this->getService(LearnerManager::class);
        $this->LearnerDashboardController = $this->getService(LearnerDashboardController::class);
        // check if connected
        if ($this->userManager->getLoggedUser() == '') {
            // not connected
            return $this->renderErrorMSG(_t('LMS_LOGGED_USERS_ONLY_HANDLER') . ' UpdateElapsedTime') ;
        }
        // check validity for user
        $learnerName = '' ;
        if (isset($_GET['learner']) || isset($_POST['learner'])) {
            if ($this->wiki->UserIsAdmin()) {
                $learnerName = (isset($_GET['learner'])) ? $_GET['learner'] :
                    ((isset($_POST['learner'])) ? $_POST['learner'] : '') ;
                if (empty($this->userManager->getOneByName($learnerName))) {
                    return $this->renderErrorMSG('In params, the user "'.$learnerName.'" does not exist !') ;
                }
            }
        }
        // get Learner
        $learner = $this->learnerManager->getLearner($learnerName) ;
        // get course
        if (isset($_GET['course']) || isset($_POST['course'])) {
            $courseTag = (isset($_GET['course'])) ? $_GET['course'] :
                ((isset($_POST['course'])) ? $_POST['course'] : '') ;
            $course = $this->courseManager->getCourse($courseTag) ;
            if (!$course) {
                return $this->renderErrorMSG('In params, the course "'.$courseTag.'" is not a course!') ;
            }
        } else {
            return $this->renderErrorMSG('The parameter "course" is required !') ;
        }
        // get module
        if (isset($_GET['module']) || isset($_POST['module'])) {
            $moduleTag = (isset($_GET['module'])) ? $_GET['module'] :
                ((isset($_POST['module'])) ? $_POST['module'] : '') ;
            $module = $this->courseManager->getModule($moduleTag) ;
            if (!$module) {
                return $this->renderErrorMSG('In params, the module "'.$moduleTag.'" is not a module!') ;
            }
            if (!$course->hasModule($moduleTag)) {
                return $this->renderErrorMSG('In params, the module "'.$moduleTag.
                    '" is a module but not a module of course "'.$courseTag.'"!') ;
            }
        } else {
            return $this->renderErrorMSG('The parameter "module" is required !') ;
        }
        // get activity
        if (isset($_GET['activity']) || isset($_POST['activity'])) {
            $activityTag = (isset($_GET['activity'])) ? $_GET['activity'] :
                ((isset($_POST['activity'])) ? $_POST['activity'] : '') ;
            $activity = $this->courseManager->getActivity($activityTag) ;
            if (!$activity) {
                return $this->renderErrorMSG('In params, the activity "'.$activityTag.'" is not an activity!') ;
            }
            if (!$module->hasActivity($activityTag)) {
                return $this->renderErrorMSG('In params, the activity "'.$activityTag.
                    '" is an activity but not an activity of module "'.$moduleTag.'"!') ;
            }
        } else {
            $activity = null ;
        }

        // check if parameters
        if (isset($_REQUEST['elapsedtime'])) {
            // update mode
            return $this->renderUpdate($learner, $course, $module, $activity) ;
        }
        // render form to ask new value
        return $this->renderForm($learner, $course, $module, $activity) ;
    }

    private function renderUpdate(Learner $learner, Course $course, Module $module, ?Activity $activity): string
    {
        
        // get elapsedtime
        if (isset($_GET['elapsedtime']) || isset($_POST['elapsedtime'])) {
            $elapsedtime = (isset($_GET['elapsedtime'])) ? $_GET['elapsedtime'] :
                ((isset($_POST['elapsedtime'])) ? $_POST['elapsedtime'] : '') ;
            $elapsedtime = intval($elapsedtime) ;
            if ($elapsedtime <0) {
                return $this->renderErrorMSG('The parameters "elapsedtime" must be positive.') ;
            }
            $elapsed_time = (new \DateTime())->setTimestamp(0)->diff(
                (new \DateTime())->setTimestamp(0)->add(new \DateInterval('PT'.$elapsedtime.'M'))
            );
            if ($elapsed_time === false) {
                return $this->renderErrorMSG('Error when importing "elapsedtime" parameter !') ;
            }
        } else {
            return $this->renderErrorMSG('The parameter "elapsedtime" is required !') ;
        }
        // update value
        if ($elapsed_time->i == 0 && $elapsed_time->h == 0 && $elapsed_time->s == 0
            && $elapsed_time->d == 0 && $elapsed_time->m == 0 && $elapsed_time->y == 0) {
            // reset elapsed time
            $updateResult = $learner->resetElapsedTime(
                $course,
                $module,
                $activity
            );
        } else {
            $updateResult = $learner->saveElapsedTime(
                $course,
                $module,
                $activity,
                $elapsed_time
            );
        }
        if (!$updateResult) {
            return $this->renderErrorMSG('The update of elapsed time for course "'.$course->getTag() .
                '", module "'.$module->getTag().'", '. (!empty($activity) ? 'activity "'.$activity->getTag(). '"': '') .
                ' and elapsed time "'.$elapsedtime.'" minutes has not worked !') ;
        }
        // redirect to page
        $this->wiki->Redirect($this->wiki->Href('', '', $this->extractPreviousParams(), false));
        return $this->renderErrorMSG('There was a trouble with redirect in renderUpdate() for UpdateElapsedTimeHandler !') ;
    }

    private function renderForm(Learner $learner, Course $course, Module $module, ?Activity $activity): string
    {
        // get all courses
        $courses = $this->courseManager->getAllCourses() ;
        $coursesStat = $this->LearnerDashboardController->processCoursesStat($courses, $learner) ;

        $output = $this->wiki->header() ;
        $previousparams = $this->extractPreviousParams() ;
        $output .= $this->render('@lms/learner-update-elapsed-time.twig', [
                'learner' => $learner,
                'course' => $course,
                'module' => $module,
                'activity' => $activity,
                'previousparamskeys' => ($previousparams) ? implode(',', array_keys($previousparams)) : '',
                'previousparams' => $previousparams,
                'tag' => $this->wiki->GetPageTag(),
                'coursesStat' => $coursesStat
            ]);
        $output .= $this->wiki->footer() ;
        return $output ;
    }

    private function renderErrorMSG(string $errorMessage): string
    {
        $output = $this->wiki->header() ;
        $output .= $this->render('@lms/alert-message.twig', [
                'alertMessage' => $errorMessage
            ]);
        $output .= $this->render('@lms/return-button.twig', [
                'tag' => $this->wiki->GetPageTag(),
                'params' => $this->extractPreviousParams()
            ]);
        $output .= $this->wiki->footer() ;
        return $output ;
    }

    private function extractPreviousParams(): ?array
    {
        if (isset($_REQUEST['previousparams'])) {
            $previousparams = (isset($_GET['previousparams'])) ?  explode(',', $_GET['previousparams']) :
                ((isset($_POST['previousparams'])) ? explode(',', $_POST['previousparams']) : null) ;
            if ($previousparams && is_array($previousparams)) {
                $params = [] ;
                foreach ($previousparams as $paramName) {
                    if (isset($_GET[$paramName])) {
                        $params[$paramName] = $_GET[$paramName] ;
                    } elseif (isset($_POST[$paramName])) {
                        $params[$paramName] = $_POST[$paramName] ;
                    }
                }
                $params =  (count($params) == 0) ? null : $params ;
            } else {
                $params = null ;
            }
        } else {
            $params = null ;
        }
        return $params ;
    }
}
