<?php

use YesWiki\Core\YesWikiAction;
use YesWiki\Lms\Controller\LearnerDashboardController;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Core\Service\UserManager;
use YesWiki\Lms\Learner;

class LearnerDashboardAction extends YesWikiAction
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
            return $this->render('@lms/alert-message.twig', [
                'alertMessage' => _t('LOGGED_USERS_ONLY_ACTION') . ' “learnerdashboard”'
            ]);
        }
        // get user name option only for admins
        if ($this->wiki->UserIsAdmin()) {
            $userNameOption = $this->wiki->GetParameter('user');
            $userNameOption = (empty($userNameOption)) ? ((empty($_REQUEST['user'])) ? '' : $_REQUEST['user']) : $userNameOption ;
        } else {
            $userNameOption = '' ;
        }
        // get learner
        $this->learner = $this->learnerManager->getLearner($userNameOption);
        if (!$this->learner) {
            // not connected
            return $this->render('@lms/alert-message.twig', [
                'alertMessage' => _t('LOGGED_USERS_ONLY_ACTION') . ' “learnerdashboard”'
            ]);
        }
        if ($this->wiki->UserIsAdmin() &&
            (empty($this->wiki->config["ADMIN_AS_USER"]) || $this->wiki->config["ADMIN_AS_USER"] == false) &&
            (empty($this->wiki->GetParameter('selectuser')) || $this->wiki->GetParameter('selectuser') ==  'true') &&
            empty($userNameOption)) {
            return $this->renderSelectUser() ;
        } else {
            return $this->renderDashboard() ;
        }
    }

    private function renderDashboard()
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
        
        return $this->render('@lms/learner-dashboard.twig', [
            'userName' => $this->learner->getUsername(),
            'courses' => $courses,
            'coursesStat' => $coursesStat,
            'display_activity_elapsed_time' => $this->wiki->config['lms_config']['display_activity_elapsed_time'],
            'use_only_custom_elapsed_time' => $this->wiki->config['lms_config']['use_only_custom_elapsed_time']
        ]);
    }
    private function renderSelectUser()
    {
        // check if user is in @admins
        if (!$this->wiki->UserIsAdmin()) {
            // not admin
            return $this->render('@lms/alert-message.twig', [
                'alertMessage' => _t('BAZ_NEED_ADMIN_RIGHTS')
            ]);
        }

        // list user
        $users = $this->userManager->getAll() ;
        $usersList = array_map(function ($user) {
            return $user['name'] ;
        }, $users) ;

        // propose form with select
        return $this->render('@lms/learner-dashboard-select-user.twig', [
            'usersList' => $usersList
            ]);
    }
}
