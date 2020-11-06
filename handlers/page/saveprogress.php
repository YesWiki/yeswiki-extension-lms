<?php
require_once LMS_PATH . 'libs/lms.lib.php';
if ($user = $this->GetUser()) {
    $fiche = $GLOBALS['bazarFiche']->getOne($this->getPageTag());
    if ($fiche['id_typeannonce'] == $this->config['lms_config']['module_form_id']
      || $fiche['id_typeannonce'] == $this->config['lms_config']['activite_form_id']) {
      $parcours = getContextualParcours();
      $parcours = array_values($parcours)[0];
      $progress = getUserProgress($user['name'], $this->getPageTag());
      if (empty($progress)) { // save current progress
        if ($fiche['id_typeannonce'] == $this->config['lms_config']['module_form_id']) {
            $mod = $fiche['id_fiche'];
            $act = '';
        } else {
            $mod = getContextualModule($parcours);
            $mod = '';
            $act = $fiche['id_fiche'];
        }
        $this->InsertTriple($user['name'], 'https://yeswiki.net/vocabulary/progress', json_encode([
            'parcours' => $parcours['id_fiche'],
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