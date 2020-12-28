<?php
require_once LMS_PATH . 'libs/lms.lib.php';
$entryManager = $this->services->get(EntryManager::class);

if ($user = $this->GetUser()) {
    $fiche = $entryManager->getOne($this->getPageTag());
    if ($fiche['id_typeannonce'] == $this->config->get('lms_config')['module_form_id']
      || $fiche['id_typeannonce'] == $this->config->get('lms_config')['activity_form_id']) {
      $course = getContextualCourse();
      $progress = getUserProgress($user['name'], $this->getPageTag());
      if (empty($progress)) { // save current progress
        if ($fiche['id_typeannonce'] == $this->config->get('lms_config')['module_form_id']) {
            $mod = $fiche['id_fiche'];
            $act = '';
        } else {
            $mod = getContextualModule($course);
            $mod = '';
            $act = $fiche['id_fiche'];
        }
        $this->InsertTriple($user['name'], 'https://yeswiki.net/vocabulary/progress', json_encode([
            'course' => $course['id_fiche'],
            'module' => $mod,
            'activity' => $act
        ]), '', '');
      }
      //$this->setMessage(implode(',', $progress));
    }
}
$iframe = testUrlInIframe() ? 'iframe' : '';
$this->redirect($this->href($iframe, $this->getPageTag()));
exit;
