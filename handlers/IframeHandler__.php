<?php

use YesWiki\Core\YesWikiHandler;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Wiki;

class IframeHandler__ extends YesWikiHandler
{
    public function run()
    {
        $commentsType = $this->params->get('comments_handler');

        $commentsHandler = (
            empty($commentsType) ||
            !is_string($commentsType)
        )
        ? ''
        : $commentsType;

        switch ($commentsHandler) {
            case 'external_humhub':
                if ($this->hasActivity(true)) {
                    $this->output = str_replace(
                        '<head>',
                        '<head data-external-comments="1">',
                        $this->output
                    );
                // todo add javascript link
                } elseif ($this->hasActivity(false)) {
                    $this->output = str_replace(
                        '<head>',
                        '<head data-external-comments="0">',
                        $this->output
                    );
                }
                //else do nothing
                break;
            case 'embedded_humhub':
                // if ($this->hasActivity(true)){
                // adjust fiche-x.tpl.html to load humhub comments
                // }
            case 'yeswiki':
            case 'discourse':
            default:
                # code...
                break;
        }
    }

    protected function hasActivity(bool $withActivatedComments): bool
    {
        $courseManager = $this->getService(CourseManager::class);
        $activity = $courseManager->getActivity($this->wiki->GetPageTag());

        return ($activity && ($activity->isCommentsEnabled() === $withActivatedComments));
    }
}
