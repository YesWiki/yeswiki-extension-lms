<?php
use YesWiki\Core\YesWikiHandler;
use YesWiki\Bazar\Service\FicheManager;

class IframeHandler__ extends YesWikiHandler
{
    function run()
    {
        $ficheManager = $this->wiki->services->get(FicheManager::class);

        if ($ficheManager->isFiche($this->wiki->GetPageTag())) {
            $output = $this->output;

            $pageBody = $this->wiki->page['body'];
//
//            'body class="yeswiki-iframe-body"';
        }
    }
}