<?php

use YesWiki\Core\YesWikiHandler;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Lms\Controller\CourseController;
use YesWiki\Lms\Service\CourseManager;

class SaveProgressHandler extends YesWikiHandler
{
    protected CourseManager $courseManager ;
    protected CourseController $courseController ;
    protected TripleStore $tripleStore ;
    public function run()
    {
        $this->courseManager = $this->getService(CourseManager::class);
        $this->courseController = $this->getService(CourseController::class);
        $this->tripleStore = $this->getService(TripleStore::class);
        // get user
        $user = $this->courseManager->getLearner();
        if ($user) {
            // connected
            // get Page Tag
            $pageTag = $this->wiki->getPageTag();
            // get Activity
            $activity = $this->courseManager->getActivity($pageTag);
            // get Module
            $module = $this->courseManager->getModule($pageTag);
            if ($activity || $module) {
                $course = $this->courseController->getContextualCourse();
                if ($activity) {
                    $module = $this->courseController->getContextualModule($course);
                }
                $progress = $user->getProgress($course, $module, $activity);
                if (empty($progress) || count($progress) == 0) { // save current progress
                    $user->setProgress($course, $module, $activity);
                }
            }
        }
        $iframe = testUrlInIframe() ? 'iframe' : '';
        $this->wiki->redirect($this->wiki->href($iframe, $this->wiki->getPageTag()));
        return ;
    }
}
