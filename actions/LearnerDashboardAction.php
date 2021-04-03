<?php

use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiAction;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\LearnerDashboardManager;
use YesWiki\Lms\Service\LearnerManager;

class LearnerDashboardAction extends YesWikiAction
{
    protected $courseManager;
    protected $userManager;
    protected $learnerManager;
    protected $learnerDashboardManager;
    protected $learner;

    public function run()
    {
        $this->courseManager = $this->getService(CourseManager::class);
        $this->learnerManager = $this->getService(LearnerManager::class);
        $this->learnerDashboardManager = $this->getService(LearnerDashboardManager::class);
        $this->userManager = $this->getService(UserManager::class);

        // user connected ?
        if (!$this->userManager->getLoggedUser()) {
            // not connected
            return $this->twig->render("@templates/alert-message.twig", [
                'type' => 'danger',
                'message' => _t('LOGGED_USERS_ONLY_ACTION') . ' (learnerdashboard)'
            ]);
        }
        // get user name option only for admins
        if ($this->wiki->UserIsAdmin()) {
            // get the learner parameter from both GET and POST
            $learnerNameOption = !empty($_REQUEST['learner']) ? $_REQUEST['learner'] : null;
        }
        // get learner
        $this->learner = $this->learnerManager->getLearner($learnerNameOption ?? null);
        if (!$this->learner) {
            // not connected
            return $this->render("@templates/alert-message.twig", [
                'type' => 'danger',
                'message' => _t('LOGGED_USERS_ONLY_ACTION') . ' (learnerdashboard)'
            ]);
        }
        
        /* * Switch to extra activity if needed * */
        if (($this->wiki->config['lms_config']['extra_activity_enabled'] ?? false) &&
            $message = $this->callAction(
                'extraactivity',
                $this->arguments + ['learners' => [$this->learner]]
            )) {
            return $message ;
        };
        /* *************************** */

        if ($this->wiki->UserIsAdmin()
            && (empty($this->wiki->GetParameter('selectuser')) || $this->wiki->GetParameter('selectuser') == 'true')
            && empty($learnerNameOption)
        ) {
            return $this->renderSelectUser();
        } else {
            return $this->renderDashboard();
        }
    }

    private function renderDashboard()
    {
        $courseTag = !empty($_GET['course']) ? $_GET['course'] : null;
        if ($courseTag) {
            // restrict to only one course
            $course = $this->courseManager->getCourse($courseTag);
            $courses = ($course) ? [$course] : null;
        }
        if (!isset($courses)) {
            // get all courses
            $courses = $this->courseManager->getAllCourses();
        }

        if (empty($_GET['learner'])) {
            $params_temp = [];
            $params_temp['learner'] = $this->learner->getUsername();
            if ($courseTag) {
                $params_temp['course'] = $courseTag;
            }
            $this->wiki->Redirect($this->wiki->Href('', '', $params_temp, false));
        }

        $coursesStat = $this->learnerDashboardManager->processCoursesStat($courses, $this->learner);

        $this->wiki->AddJavascriptFile('tools/lms/presentation/javascript/collapsible-panel.js');
        return $this->render('@lms/learner-dashboard.twig', [
            'learner' => $this->learner,
            'courses' => $courses,
            'coursesStat' => $coursesStat,
            'display_activity_elapsed_time' => $this->wiki->config['lms_config']['display_activity_elapsed_time'],
            'use_only_custom_elapsed_time' => $this->wiki->config['lms_config']['use_only_custom_elapsed_time'],
            'user_is_admin' => $this->wiki->UserIsAdmin(),
            'extraActivityMode' => $this->wiki->config['lms_config']['extra_activity_enabled'] ?? false,
        ]);
    }

    private function renderSelectUser()
    {
        // check if user is in @admins
        if (!$this->wiki->UserIsAdmin()) {
            // not admin
            return $this->render("@templates/alert-message.twig", [
                'type' => 'danger',
                'message' => _t('BAZ_NEED_ADMIN_RIGHTS')
            ]);
        }

        // list user
        $users = $this->userManager->getAll();
        $usersList = array_map(function ($user) {
            $learner = $this->learnerManager->getLearner($user['name']);
            return ['tag' => $learner->getUsername(), 'fullname' => $learner->getFullname()];
        }, $users);

        // propose form with select
        return $this->render('@lms/learner-dashboard-select-user.twig', [
            'usersList' => $usersList
        ]);
    }
}
