<?php
include_once 'tools/lms/libs/lms.lib.php';
$pageTag = $this->getPageTag();
$ajaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$getParams = [];
if ($ajaxRequest) {
    header('Content-type: application/json; charset=UTF-8');
} else {
    $getParams = (!empty($_GET['course']) && $_GET['course'] ? ['course' => $_GET['course']] : [])
        + (!empty($_GET['module']) && $_GET['module'] ? ['module' => $_GET['module']] : []);
}
if ($user = $this->GetUser()) {
    if (!empty($_GET['id'])) {
        //get reactions from user for this page
        $r = getUserReactionOnPage($pageTag, $user['name']);
        if (!empty($r)) {
            // erase past reaction
            $sql = 'DELETE FROM ' . $this->GetConfigValue('table_prefix') . 'triples ' . 'WHERE resource = "' . $pageTag . '" ' . 'AND property = "https://yeswiki.net/vocabulary/reaction" AND value LIKE \'%"user":"'.$user['name'].'"%\';';
            $this->query($sql);
        }
        if (empty($r) || (!empty($r['id']) && $_GET['id'] != $r['id'])) {
            // create new reaction
            $this->InsertTriple($pageTag, 'https://yeswiki.net/vocabulary/reaction', json_encode(['user' => $user['name'], 'id' => $_GET['id']]), '', '');
        }
        if ($ajaxRequest) {
            echo json_encode(['state' => 'success']);
        } else {
            $this->redirect($this->href(testRefererUrlInIframe(), '', $getParams, false));
        }
    } else {
        if ($ajaxRequest) {           
            echo json_encode(['state' => 'error', 'errorMessage' => 'Un type de réaction doit être présent dans l\'url.']);
        } else {
            $this->setMessage('Un type de réaction doit être présent dans l\'url.');
            $this->redirect($this->href(testRefererUrlInIframe(), '', $getParams, false));
        }
    }
} else {
    if ($ajaxRequest) {           
        echo json_encode(['state' => 'error', 'errorMessage' => 'Vous devez être connecté pour réagir.']);
    } else {
        $this->setMessage('Vous devez être connecté pour réagir.');
        $this->redirect($this->href(testRefererUrlInIframe(), '', $getParams, false));
    }
}