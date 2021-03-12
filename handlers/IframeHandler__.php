<?php

use YesWiki\Core\YesWikiHandler;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Wiki;

class IframeHandler__ extends YesWikiHandler
{
    function run()
    {
        $config = $this->wiki->config;

        // if the use_yeswiki_comments is false in the config and the current page is an activity, see if
        // the activity have its comments enabled. If yes, add the 'data-external-comments' attribute to body to alert
        // a potential social platform the content need comments
        // TODO see if use_yeswiki_comments is still used and if not, think about another parameter name
        if (isset($config['lms_config']['use_yeswiki_comments']) && !$config['lms_config']['use_yeswiki_comments']) {

            $courseManager = $this->wiki->services->get(CourseManager::class);
            $activity = $courseManager->getActivity($this->wiki->GetPageTag());

            if ($activity && !$activity->isCommentsEnabled()) {
                $this->output = str_replace('<head>',
                    '<head data-external-comments="0">',
                    $this->output);
            }
        }
    }
}