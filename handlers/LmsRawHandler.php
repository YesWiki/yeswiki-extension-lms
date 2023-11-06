<?php

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\YesWikiHandler;

class LmsRawHandler extends YesWikiHandler
{

    public function run()
    {
        $aclService = $this->getService(AclService::class);
        $entryManager = $this->getService(EntryManager::class);
        $activityEntry = $entryManager->getOne($this->wiki->tag);
        if ($activityEntry && intval($activityEntry['id_typeannonce']) != $this->wiki->config['lms_config']['activity_form_id']) {
            return;
        } else {
            header("Content-type: text/plain; charset=".YW_CHARSET);
            // display raw page
            return _convert($activityEntry['bf_contenu'], YW_CHARSET);
        }
    }
}