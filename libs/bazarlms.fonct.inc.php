<?php
/**
 * Extension of bazar for the LMS module
 *
 * @category YesWiki
 * @package  lms
 * @author   Adrien Cheype <adrien.cheype@gmail.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

 /**
  * Helper to get previous value
  *
  * @param string $key
  * @param array $hash
  * @return void
  */
function getPrevValue($key, $hash = array())
{
    $found_index = array_search($key, $hash);
    if ($found_index === false || $found_index === 0) {
        return false;
    }
    return $hash[$found_index-1];
}

/**
 * Display the 'Précédent', 'Suivant' and 'Fait !' buttons which permits to a learner to navigate in an activity page
 * Must be declare in the bazar form definition as followed :
 *    'navigationactivite***bf_navigation*** *** *** *** *** *** *** *** ***'
 * The second position value is the name of the entry field.
 * If the word 'module_modal' is written at the third position, the links which refer to the modules are opened in a
 * modal box.
 *
 * cf. formulaire.fonct.inc.php of the bazar extension to see the other field definitions
 *
 * @param array   $formtemplate
 * @param array   $tableau_template The bazar field definition inside the form definition
 * @param string  $mode  Action type for the form : 'saisie', 'requete', 'html', ...
 * @param array   $fiche  The entry which is displayed or modified
 * @return string Return the generated html to include
 */
function navigationactivite(&$formtemplate, $tableau_template, $mode, $fiche){

    // load the lms lib
    require_once LMS_PATH . 'libs/lms.lib.php';

    // the tag of the current activity page
    $currentPageTag =  !empty($fiche['id_fiche']) ? $fiche['id_fiche'] : '';

    $output = '';
    if ($mode == 'html' && $currentPageTag) {
        if ($GLOBALS['wiki']->config['lms_config']['use_tabs']) {
            // if a number is at the end of the page tag, it means that it's a tab page corresponding to the page without the number
            // thus, to associate this tab page to its parent one, we remove the number from the page tag
            $currentPageTag = preg_replace('/[0-9]*$/', '', $currentPageTag);
        }

        // the consulted parcours entry
        $parcoursEntry = getContextualParcours();
        // the consulted module entry to display the current activity
        $currentModule = getContextualModule($parcoursEntry);

        // true if the module links are opened in a modal box
        $moduleModal = $tableau_template[2] == 'module_modal';

        if ($currentPageTag && $currentModule && $parcoursEntry) {
            $output .= '<nav aria-label="navigation"' . (!empty($tableau_template[1]) ? ' data-id="' . $tableau_template[1]
                . '"' : '') .  '>
            <ul class="pager pager-lms">';
            
            $activityId = "checkboxfiche" . $GLOBALS['wiki']->config['lms_config']['activite_form_id'];
            $allActivities = [];
            if (isset($currentModule[$activityId])) {    
                $allActivities = explode(',', $currentModule[$activityId]);
            }

            $modulesId = "checkboxfiche" . $GLOBALS['wiki']->config['lms_config']['module_form_id'];
            $allModules = [];
            if (isset($parcoursEntry[$modulesId])) {
                $allModules = explode(',', $parcoursEntry[$modulesId]);
            }

            // display the previous button
            if ($currentPageTag == reset($allActivities)) {
                // if first activity of a module, the previous link is to the current module entry
                $output .= '<li class="previous"><a href="' . $GLOBALS['wiki']->href('', $currentModule['id_fiche'])
                    . '&parcours=' . $parcoursEntry['id_fiche']
                    . '"' . ($moduleModal ? ' class="bazar-entry modalbox"' : '')
                    . '><span aria-hidden="true">&larr;</span>&nbsp;' . _t('LMS_PREVIOUS') . '</a></li>';
            } elseif ($previousActivityTag = getPrevValue($currentPageTag, $allActivities)) {
                // otherwise, the previous link is to the previous activity
                $output .= '<li class="previous"><a href="' . $GLOBALS['wiki']->href('', $previousActivityTag)
                    . '&parcours=' . $parcoursEntry['id_fiche'] . '&module=' . $currentModule['id_fiche']
                    . '"><span aria-hidden="true">&larr;</span>&nbsp;' . _t('LMS_PREVIOUS') . '</a></li>';
            }

            // display the next button
            if ($currentPageTag == end($allActivities)) {
                if ($currentModule['id_fiche'] != end($allModules)) {
                    // if last activity of the module and not the last activity, the next link is to the next module entry
                    // (no next button is showed at the last activity of the last module)
                    $nextModuleTag = $allModules[array_search($currentModule['id_fiche'], $allModules) + 1];
                    $output .= '<li class="next"><a href="' . $GLOBALS['wiki']->href('', $nextModuleTag)
                        . '&parcours=' . $parcoursEntry['id_fiche']
                        . '"' . ($moduleModal ? ' class="bazar-entry modalbox"' : '')
                        . '>' . _t('LMS_NEXT') . '&nbsp;<span aria-hidden="true">&rarr;</span></a></li>';
                }
            } else {
                // otherwise, the next link is to the next activity
                $nextActivityTag = $allActivities[array_search($currentPageTag, $allActivities) + 1];
                $output .= '<li class="next"><a href="' . $GLOBALS['wiki']->href('', $nextActivityTag)
                    . '&parcours=' . $parcoursEntry['id_fiche'] . '&module=' . $currentModule['id_fiche']
                    . '">' . _t('LMS_NEXT') . '&nbsp;<span aria-hidden="true">&rarr;</span></a></li>';
            }

            $output .= '</ul>
            </nav>';
        }
    }
    return $output;
}

/**
 * Display the different options to navigate into a module according to module field 'Activé' and the navigation of the learner.
 * Must be declare in the bazar form definition as followed :
 *    'navigationmodule**bf_navigation*** *** *** *** *** *** *** *** ***'
 * The second position value is the name of the entry field.
 *
 * cf. formulaire.fonct.inc.php of the bazar extension to see the other field definitions
 *
 * @param array   $formtemplate
 * @param array   $tableau_template The bazar field definition inside the form definition
 * @param string  $mode  Action type for the form : 'saisie', 'requete', 'html', ...
 * @param array   $fiche  The entry which is displayed or modified
 * @return string Return the generated html to include
 */
function navigationmodule(&$formtemplate, $tableau_template, $mode, $fiche){

    // the tag of the current module page
    $currentEntryTag =  !empty($fiche['id_fiche']) ? $fiche['id_fiche'] : '';

    // does the entry is viewed inside a modal box ? $moduleModal is true when the page was called in ajax
    $moduleModal = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    $output = '';
    if ($mode == 'html' && $currentEntryTag){
        // load the lms lib
        require_once LMS_PATH . 'libs/lms.lib.php';
        // add LMS extension css style
        $GLOBALS['wiki']->AddCSSFile(LMS_PATH . 'presentation/styles/lms.css');

        // the consulted parcours entry
        $parcoursEntry = getContextualParcours();
        $activityId = "checkboxfiche" . $GLOBALS['wiki']->config['lms_config']['activite_form_id'];
        $allActivities = [];
        if (isset($fiche[$activityId])) {    
            $allActivities = explode(',', $fiche[$activityId]);
        }

        $modulesId = "checkboxfiche" . $GLOBALS['wiki']->config['lms_config']['module_form_id'];
        $allModules = [];
        if (isset($fiche[$modulesId])) {
            $allModules = explode(',', $parcoursEntry[$modulesId]);
        }

        $output .= '<nav aria-label="navigation"' . (!empty($tableau_template[1]) ? ' data-id="' . $tableau_template[1]
                . '"' : '') .  '> 
            <ul class="pager pager-lms">';

        // check the access to the module
        if (empty($allActivities) || empty($fiche['listeListeOuinonLmsbf_active']) || $fiche['listeListeOuinonLmsbf_active'] == 'non') {
            // if the module has any activity or if the module is desactivated, inform the learner he doesn't have access to him
            $output .= '<li class="noaccess">' . _t('LMS_MODULE_NOACCESS') . '</li>';
        } else {
            // otherwise display the button 'Commencer'
            $firstActivity = reset($allActivities);
            $output .= '<li class="center"><a href="' . $GLOBALS['wiki']->href('', $firstActivity)
                . '&parcours=' . $parcoursEntry['id_fiche'] . '&module=' . $currentEntryTag
                . '">' . _t('LMS_BEGIN') . '</a></li>';
        }

        // we show the previous and next button only if it's in a modal
        if ($moduleModal) {
            // display the next button
            if ($currentEntryTag != end($allModules)) {
                // if not the last module of the parcours, a link to the next module is displayed
                $moduleIndex = array_search($currentEntryTag, $allModules);
                if ($moduleIndex) {
                    $nextModuleTag = $allModules[$moduleIndex + 1];
                    $output .= '<li class="next square" title="' . _t('LMS_MODULE_NEXT')
                        . '"><a href="' . $GLOBALS['wiki']->href('', $nextModuleTag) . '&parcours=' . $parcoursEntry['id_fiche']
                        . '" "aria-label="' . _t('LMS_NEXT')
                        . '"' . ($moduleModal ? ' class="bazar-entry modalbox"' : '')
                        . '>' . '<i class="fa fa-caret-right" aria-hidden="true"></i></a></li>';
                }
            }
            // display the previous button
            if ($currentEntryTag != reset($allModules)) {
                // if not the first module of the parcours, a link to the previous module is displayed
                $moduleIndex = array_search($currentEntryTag, $allModules);
                if ($moduleIndex) {
                    $previousModuleTag = $allModules[$moduleIndex - 1];
                    $output .= '<li class="next square" title="' . _t('LMS_MODULE_PREVIOUS')
                        . '"><a href="' . $GLOBALS['wiki']->href('', $previousModuleTag) . '&parcours=' . $parcoursEntry['id_fiche']
                        . '" "aria-label="' . _t('LMS_PREVIOUS')
                        . '"' . ($moduleModal ? ' class="bazar-entry modalbox"' : '')
                        . '><i class="fa fa-caret-left" aria-hidden="true"></i></a></li>';
                }
            }
        }

        $output .= '</ul>
            </nav>';
    }
    return $output;
}

/**
 * Display the 'Return' button which permit to come back to the calling page (history back). The button is displayed only
 * in 'view' mode and if the entry is not opened from a modal.
 * Must be declare in the bazar form definition as followed :
 *    'boutonretour*** *** *** *** *** *** *** *** *** ***'
 *
 * cf. formulaire.fonct.inc.php of the bazar extension to see the other field definitions
 *
 * @param array   $formtemplate
 * @param array   $tableau_template The bazar field definition inside the form definition
 * @param string  $mode  Action type for the form : 'saisie', 'requete', 'html', ...
 * @param array   $fiche  The entry which is displayed or modified
 * @return string Return the generated html to include
 */
function boutonretour(&$formtemplate, $tableau_template, $mode, $fiche){

    // the tag of the current entry
    $currentEntryTag = !empty($fiche['id_fiche']) ? $fiche['id_fiche'] : '';

    if ($mode == 'html' && $currentEntryTag) {
        // does the entry is viewed inside a modal box ? $moduleModal is true when the page was called in ajax
        $entryModal = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        // display the button if it's not inside a modal box
        if (!$entryModal)
            return '<div class="BAZ_boutonretour" style="margin-top: 30px;"><a class="btn btn-xs btn-secondary-1" href="javascript:history.back()">'
                . '<i class="fas fa-arrow-left"></i>&nbsp;' . _t('LMS_RETURN_BUTTON') . '</a></div>';
    }
}
