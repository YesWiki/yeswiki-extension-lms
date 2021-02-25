<?php

use YesWiki\Core\YesWikiAction;
// use YesWiki\Lms\Controller\LearnerDashboardController;
// use YesWiki\Lms\Service\CourseManager;
// use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Core\Service\UserManager;
// use YesWiki\Lms\Learner;
use YesWiki\Wiki;

class ImportCoursesAction extends YesWikiAction
{
    protected $courseManager ;
    protected $userManager;
    protected $learnerManager ;
    protected $LearnerDashboardController ;
    protected $learner ;

    public function run()
    {
        $this->userManager = $this->getService(UserManager::class);
        // user connected ?
        if (!$this->userManager->getLoggedUser()) {
            // not connected
            return $this->render('@templates/alert-message.twig', [
                'alertMessage' => _t('LOGGED_USERS_ONLY_ACTION') . ' “importcourses”'
            ]);
        }

        //display ajax import form
        return $this->render('@lms/import-form.twig', [
            'url' => $this->wiki->href('', $this->wiki->getPageTag())
        ]);
    }
}
