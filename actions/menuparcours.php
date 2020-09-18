<?php
/**
 * menuparcours : action which displays the menu of a specific course (parcours)
 *
 * @category YesWiki
 * @package  lms
 * @author   Adrien Cheype <adrien.cheype@gmail.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

// load the lms lib
require_once LMS_PATH . 'libs/lms.lib.php';
// js lib
$this->AddJavascriptFile('tools/bazar/libs/bazar.js');

//$user = $this->GetUser();
// the action only works if a user is logged in
//if (!empty($user)) {

    // Read the action parameters
    // css class for the action
    $class = $this->GetParameter("class");

    // the parcours to display
    $parcoursEntry = getContextualParcours();

    // the consulted module to display the current activity
    $currentModule = getContextualModule($parcoursEntry);

    // display the menu only if a contextual parcours is found
    if ($parcoursEntry) {
        $allModules = explode(',', $parcoursEntry["checkboxfiche" . $this->config['lms_config']['module_form_id']]);

        // first module to display
        // if not defined, or the one defined doesn't exist or isn't a module entry, the first module is by default
        // the first one of parcours
        $moduleDebutTag = $this->GetParameter("moduledebut");
        if (empty($moduleDebutTag)) {
            $moduleDebutTag = reset($allModules);
        }
        $moduleDebutEntry = ($moduleDebutTag == $currentModule['id_fiche']) ? $currentModule : baz_valeurs_fiche($moduleDebutTag);
        if (!$moduleDebutEntry || $moduleDebutEntry['id_typeannonce'] != $this->config['lms_config']['module_form_id']) {
            $moduleDebutTag = reset($allModules);
            $moduleDebutEntry = ($moduleDebutTag == $currentModule['id_fiche']) ? $currentModule : baz_valeurs_fiche($moduleDebutTag);
        }

        // last module to display
        // if not defined, or the one defined doesn't exists or isn't a module entry, the last module is by default
        // the last of the parcours
        $moduleFinTag = $this->GetParameter("modulefin");
        if (empty($moduleFinTag)) {
            $moduleFinTag = end($allModules);
        }
        $moduleFinEntry = ($moduleFinTag == $currentModule['id_fiche']) ? $currentModule : baz_valeurs_fiche($moduleFinTag);
        if (!$moduleFinEntry || $moduleFinEntry['id_typeannonce'] != $this->config['lms_config']['module_form_id']) {
            $moduleFinTag = $moduleFinTag = end($allModules);
            $moduleFinEntry = ($moduleFinTag == $currentModule['id_fiche']) ? $currentModule : baz_valeurs_fiche($moduleFinTag);
        }

        // set the subarray of modules between moduledebut and modulefin
        $moduleDebutInd = array_search($moduleDebutTag, $allModules);
        $moduleFinInd = array_search($moduleFinTag, $allModules);
        $modulesDisplayed = [];
        for ($i = $moduleDebutInd; $i <= $moduleFinInd; $i++) {
            if ($i == $moduleDebutInd)
                $modulesDisplayed[] = $moduleDebutEntry;
            else if ($i == $moduleFinInd)
                $modulesDisplayed[] = $moduleFinEntry;
            else
                $modulesDisplayed[] = baz_valeurs_fiche($allModules[$i]);
        }

        // find the menu template
        $template = $this->GetParameter("template");
        if (empty($template) || !file_exists(LMS_PATH . 'presentation/templates/' . $template)) {
            $template = "menu-lms.tpl.html";
        }

        // display the menu with the template
        include_once 'includes/squelettephp.class.php';
        try {
            $squel = new SquelettePhp($template, 'lms');
            $content = $squel->render(
                array(
                    "parcoursTag" => $parcoursEntry['id_fiche'],
                    "currentModule" => $currentModule,
                    "modulesDisplayed" => $modulesDisplayed,
                )
            );
        } catch (Exception $e) {
            $content = '<div class="alert alert-danger">' . _t('LMS_MENUPARCOURS_ERROR') . $e->getMessage() . '</div>' . "\n";
        }

        echo (!empty($class)) ? '<div class="' . $class . '">' . "\n" . $content . "\n" . '</div>' . "\n" : $content;
    //}
}
