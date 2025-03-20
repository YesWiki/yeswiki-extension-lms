<?php

use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\YesWikiMigration;

class LmsInitialDatabaseAndPagesSetup extends YesWikiMigration
{
    public function run(): void
    {
        $pageManager = $this->getService(PageManager::class);
        $tripleStore = $this->getService(TripleStore::class);
        $formManager = $this->getService(FormManager::class);

        $glob = glob('tools/lms/setup/lists/*.json');
        foreach ($glob as $filename) {
            $listname = str_replace(['tools/lms/setup/lists/', '.json'], '', $filename);
            if (file_exists($filename) && !$pageManager->getOne($listname)) {
                // save the page with the list value
                $pageManager->save($listname, file_get_contents($filename));
                // in case, there is already some triples for the list, delete them
                $tripleStore->delete($listname, 'http://outils-reseaux.org/_vocabulary/type', null);
                // create the triple to specify this page is a list
                $tripleStore->create($listname, 'http://outils-reseaux.org/_vocabulary/type', 'liste', '', '');
            }
        }

        $glob = glob('tools/lms/setup/forms/*.json');
        foreach ($glob as $filename) {
            $formFilename = str_replace(['tools/lms/setup/forms/', '.json'], '', $filename);
            $formId = $this->wiki->config['lms_config'][$formFilename.'_form_id'];
            // test if the form exists, if not, install it
            $form = $formManager->getOne($formId);
            if (empty($form)) {
                $rawContent = file_get_contents($filename);
                $rawContent = str_replace(
                    ['{{formSheetId}}', '{{formActivityId}}', '{{formModuleId}}', '{{formCourseId}}'],
                    [
                      $this->wiki->config['lms_config']['attendance_sheet_form_id'],
                      $this->wiki->config['lms_config']['activity_form_id'],
                      $this->wiki->config['lms_config']['module_form_id'],
                      $this->wiki->config['lms_config']['course_form_id'],
                    ],
                    $rawContent
                );
                $form = json_decode($rawContent, true);
                $formManager->create($form);
            }
        }
    }
}
