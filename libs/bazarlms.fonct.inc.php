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
 * Get the activities of the current module
 * @param array $currentModule The current module entry
 * @return array The activites
 */
function getActivities($currentModule)
{
    $activityId = "checkboxfiche" . $GLOBALS['wiki']->config['lms_config']['activite_form_id'];
    $allActivities = [];
    if (isset($currentModule[$activityId])) {
        $allActivities = explode(',', $currentModule[$activityId]);
    }
    return $allActivities;
}

/**
 * Get the modules of the current parcours
 * @param array $parcoursEntry The current parcours entry
 * @return array The modules
 */
function getModules(array $parcoursEntry)
{
    $modulesId = "checkboxfiche" . $GLOBALS['wiki']->config['lms_config']['module_form_id'];
    $allModules = [];
    if (isset($parcoursEntry[$modulesId])) {
        $allModules = explode(',', $parcoursEntry[$modulesId]);
    }
    return array($allModules, $parcoursEntry);
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
        $parcoursEntry = array_values($parcoursEntry)[0];
        // the consulted module entry to display the current activity
        $currentModule = getContextualModule($parcoursEntry);

        // true if the module links are opened in a modal box
        $moduleModal = $tableau_template[2] == 'module_modal';

        if ($currentPageTag && $currentModule && $parcoursEntry) {
            $output .= '<nav aria-label="navigation"' . (!empty($tableau_template[1]) ? ' data-id="' . $tableau_template[1]
                . '"' : '') .  '>
            <ul class="pager pager-lms">';

            $allActivities = getActivities($currentModule);
            $allModules = getModules($parcoursEntry);

            // display the previous button
            if ($currentPageTag == reset($allActivities)) {
                // if first activity of a module, the previous link is to the current module entry
                $output .= '<li class="previous"><a href="' . $GLOBALS['wiki']->href('', $currentModule['id_fiche'])
                    . (empty($parcoursEntry['id_fiche']) ?  '' : '&parcours=' . $parcoursEntry['id_fiche'])
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
                        . (empty($parcoursEntry['id_fiche']) ?  '' : '&parcours=' . $parcoursEntry['id_fiche'])
                        . '"' . ($moduleModal ? ' class="bazar-entry modalbox"' : '')
                        . '>' . _t('LMS_NEXT') . '&nbsp;<span aria-hidden="true">&rarr;</span></a></li>';
                }
            } else {
                // otherwise, the next link is to the next activity
                $nextActivityTag = $allActivities[array_search($currentPageTag, $allActivities) + 1];
                $output .= '<li class="next"><a href="' . $GLOBALS['wiki']->href('', $nextActivityTag)
                    . (empty($parcoursEntry['id_fiche']) ?  '' : '&parcours=' . $parcoursEntry['id_fiche'])
                    . '&module=' . $currentModule['id_fiche']
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

        $allActivities = getActivities($fiche);
        $allModules = getModules($parcoursEntry);

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
            $output .= '<li class="center lms-begin"><a class="launch-module" href="' . $GLOBALS['wiki']->href('', $firstActivity)
                . (empty($parcoursEntry['id_fiche']) ?  '' : '&parcours=' . $parcoursEntry['id_fiche'])
                . '&module=' . $currentEntryTag
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

/**
 * Display the possible reactions to comment an activity.
 * Must be declare in the bazar form definition as followed :
 *    'reactions***idreaction1,idreaction2,idreaction3***titlereaction1,titlereaction2,titlereaction3***image1,image2,image3*** *** *** *** *** *** ***'
 * Some ids are generic and have associated images and titles : j-ai-appris,j-aime,pas-clair,pas-compris,pas-d-accord,top-gratitude
 * otherwise, you will need to give a filename that is included in files directory
 * 
 * cf. formulaire.fonct.inc.php of the bazar extension to see the other field definitions
 *
 * @param array   $formtemplate
 * @param array   $tableau_template The bazar field definition inside the form definition
 * @param string  $mode  Action type for the form : 'saisie', 'requete', 'html', ...
 * @param array   $fiche  The entry which is displayed or modified
 * @return string Return the generated html to include
 */
function reactions(&$formtemplate, $tableau_template, $mode, $fiche) {

    // the tag of the current entry
    $currentEntryTag = !empty($fiche['id_fiche']) ? $fiche['id_fiche'] : '';

    if ($mode == 'html' && $currentEntryTag) {
        // load the lms lib
        require_once LMS_PATH . 'libs/lms.lib.php';

        $ids = explode(',', $tableau_template[2]);
        $ids = array_map('trim', $ids);
        // if empty, we use default values
        if (count($ids) == 1 && empty($ids[0])) {
            $ids = ['top-gratitude', 'j-aime', 'j-ai-appris', 'pas-compris', 'pas-d-accord', 'idee-noire'];
        }
        $titles = explode(',', $tableau_template[3]);
        $titles = array_map('trim', $titles);
        $images = explode(',', $tableau_template[4]);
        $images = array_map('trim', $images);
        // TODO : check realpath for security 
        // $images = array_map('realpath', $images);
        $outputreactions = '';
        // get reactions numbers for templating later
        $r = getAllReactions($fiche['id_fiche'], $ids, $GLOBALS['wiki']->getUserName());

        foreach($ids as $k => $id) {
            if (empty($titles[$k])) { // if ids are default ones, we have some titles
                switch ($id) {
                    case 'j-ai-appris':
                        $title =  "J'ai appris quelque chose";
                        break;
                    case 'j-aime':
                        $title =  "J'aime";
                        break;
                    case 'idee-noire':
                        $title =  "Ca me perturbe";
                        break;
                    case 'pas-compris':
                        $title =  "J'ai pas compris";
                        break;
                    case 'pas-d-accord':
                        $title =  "Je ne suis pas d'accord";
                        break;
                    case 'top-gratitude':
                        $title =  "Gratitude";
                        break;
                    default:
                        $title = $id;  // we show just the id, as it's our only information available
                        break;
                }
            } else {
                $title = $titles[$k]; // custom title
            }
            if (empty($images[$k])) { // if ids are default ones, we have some images
                switch ($id) {
                    case 'j-ai-appris':
                    case 'j-aime':
                    case 'idee-noire':
                    case 'pas-compris':
                    case 'pas-d-accord':
                    case 'top-gratitude':
                        $image =  LMS_PATH . 'presentation/images/mikone-'.$id.'.svg';
                        break;
                    default:
                        $image = false;
                        break;
                }
            } else {
                if (file_exists('files/'.$images[$k])) { // custom image in files folder
                    $image = 'files/'.$images[$k];
                } elseif (file_exists( LMS_PATH . 'presentation/images/mikone-'.$images[$k].'.svg')) {
                    $image =  LMS_PATH . 'presentation/images/mikone-'.$id.'.svg';  
                } else {
                    $image = false;
                }
            }
            if (!$image) {
                $reaction = '<div class="alert alert-danger">Image non trouvée...</div>';
            } else {
                $nbReactions = $r['reactions'][$id];
                $reaction = '<img class="reaction-img" alt="icon '.$id.'" src="'.$image.'" />
                    <h6 class="reaction-title">'.$title.'</h6>
                    <div class="reaction-numbers">'.$nbReactions.'</div>';
            }
            $outputreactions .= '<div class="reaction-content">';
            if ($GLOBALS['wiki']->getUser()) {
                $extraClass = (!empty($r['userReaction']) && $id == $r['userReaction']) ? ' user-reaction' : '';
                $outputreactions .= '<a href="'.$GLOBALS['wiki']->href('reaction', '', 'id='.$id).'" class="add-reaction'.(!empty($extraClass) ? ''.$extraClass : '').'">'.$reaction.'</a>';
            } else {
                $outputreactions .= '<a href="#" onclick="return false;" title="Pour réagir, identifiez-vous!" class="disabled add-reaction">'.$reaction.'</a>';
            }
            $outputreactions .= '</div>';
        }
        if ($GLOBALS['wiki']->getUser()) {
            $msg = 'Partagez votre réaction à propos de ce contenu';
        } else {
            $msg = 'Pour vous permettre de réagir, <a href="#LoginModal" class="btn btn-primary" data-toggle="modal">veuillez vous identifier</a>';
        }
        $output = '<hr /><div class="reactions-container"><h5>'.$msg.'</h5><div class="reactions-flex">'.$outputreactions.'</div>';
        if ($GLOBALS['wiki']->getUser()) {
            $output .= '<em>Et n\'hésitez pas à faire un commentaire pour approndir la réflexion!</em>';
        }
        $output .= '</div>'."\n";
        return $output;
    }
}
