<?php

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiHandler;

class IframeHandler__ extends YesWikiHandler
{
    function run()
    {
        $entryManager = $this->wiki->services->get(EntryManager::class);

        if ($entryManager->isEntry($this->wiki->GetPageTag())) {
            $output = $this->output;

            $pageBody = $this->wiki->page['body'];

            // 'body class="yeswiki-iframe-body"';

            // WIP for ListOuinonLmsbf_commentaires and external comments
            // need before to finish Iframe handler refactoring in the core
        }
    }
}