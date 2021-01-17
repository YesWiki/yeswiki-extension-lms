<?php

use YesWiki\Core\YesWikiHandler;
use YesWiki\Core\Service\UserManager;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\LearnerManager;

class UpdateElapsedTimeHandler extends YesWikiHandler
{
    protected $userManager;
    protected $courseManager;
    protected $learnerManager ;

    public function run()
    {
        $this->userManager = $this->getService(UserManager::class);
        $this->courseManager = $this->getService(CourseManager::class);
        $this->learnerManager = $this->getService(LearnerManager::class);
        // check if connected
        if ($this->userManager->getLoggedUser() == ''){
            // not connected
            return $this->renderErrorMSG(_t('LMS_LOGGED_USERS_ONLY_HANDLER') . ' UpdateElapsedTime') ;
        }
        // check if parameters
        if (isset($_REQUEST['course']) || isset($_REQUEST['module']) || isset($_REQUEST['activity'])) {
            // update mode
            return $this->renderUpdate() ;
        }
        // render form to ask new value
        return $this->renderForm() ;
    }

    private function renderUpdate(): string
    {
        // check validity for user
        $user = '' ;
        if (isset($_GET['user']) || isset($_POST['user'])) {
            if ($this->wiki->UserIsAdmin()) {
                $user = (isset($_GET['user'])) ? $_GET['user'] :
                    ((isset($_POST['user'])) ? $_POST['user'] : '' ) ;
                if (empty($this->userManager->getOneByName($user))) {
                    return $this->renderErrorMSG('In params, the user "'.$user.'" does not exist !') ;
                }
            } 
        }
        // get Learner
        $learner = $this->learnerManager->getLearner($user) ;
        // get course
        if (isset($_GET['course']) || isset($_POST['course'])) {
            $courseTag = (isset($_GET['course'])) ? $_GET['course'] :
                ((isset($_POST['course'])) ? $_POST['course'] : '' ) ;
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
                ((isset($_POST['module'])) ? $_POST['module'] : '' ) ;
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
                ((isset($_POST['activity'])) ? $_POST['activity'] : '' ) ;
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
        // get elapsedtime
        if (isset($_GET['elapsedtime']) || isset($_POST['elapsedtime'])) {
            $elapsedtime = (isset($_GET['elapsedtime'])) ? $_GET['elapsedtime'] :
                ((isset($_POST['elapsedtime'])) ? $_POST['elapsedtime'] : '' ) ;
            $elapsedtime = intval($elapsedtime) ;
            if ($elapsedtime <0) {
                return $this->renderErrorMSG('The parameters "elapsedtime" must be positive.') ;
            }
            $elapsed_time = (new \DateTime())->setTimestamp(0)->diff(
                (new \DateTime())->setTimestamp(0)->add(new \DateInterval('PT'.$elapsedtime.'M')));
            if ($elapsed_time === false) {
                return $this->renderErrorMSG('Error when importing "elapsedtime" parameter !') ;
            }
        } else {
            return $this->renderErrorMSG('The parameter "elapsedtime" is required !') ;
        }
        // update value
        $updateResult = $learner->saveElapsedTime(
            $course,
            $module,
            $activity,
            $elapsed_time
        );
        if (!$updateResult) {
            return $this->renderErrorMSG('The update of elapsed time for course "'.$courseTag .
                '", module "'.$moduleTag.'", '. (!empty($activityTag) ? 'activity "'.$activityTag. '"': '') .
                ' and elapsed time "'.$elapsedtime.'" minutes has not worked !') ;
        }
        // redirect to page
        $this->wiki->Redirect($this->wiki->Href());
        return '' ;
    }

    private function renderForm(): string
    {
        return $this->renderErrorMSG('Work in progress !') ;
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
