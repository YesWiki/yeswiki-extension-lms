<?php
/**
 * Library of the LMS users functions
 *
 * @category YesWiki
 * @package  lms
 * @author   Adrien Cheype <adrien.cheype@gmail.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

/**
 * Get the contextual parcours according to the Get parameter 'parcours' and the existing parcours. By order :
 *
 *   - if the Get parameter 'parcours' refers to a tag associated to a parcours entry, return it
 *   - if not, return false
 *   - if there is at least one parcours in the database, return the first created one
 *   - if not, return false
 *
 * @return array The parcours entry
 */
function getContextualParcours(){
    $parcoursTag = $GLOBALS['wiki']->GetParameter('parcours');
    if (!empty($parcoursTag)) {
        $parcoursEntry = baz_valeurs_fiche($parcoursTag);
        if ($parcoursEntry && $parcoursEntry['id_typeannonce'] == $GLOBALS['wiki']->config['lms_config']['parcours_form_id'])
            return $parcoursEntry;
    } else {
        $entries = baz_requete_recherche_fiches('', '', $GLOBALS['wiki']->config['lms_config']['parcours_form_id'], '', 1, '');
        if (!empty($entries)) {
            return json_decode($entries[0]['body'], true);
        }
    }
    return false;
}

/**
 * Get the contextual module according to the given 'parcours' entry, its modules, the Get parameter 'module' and the current page.
 * Caution : work only from a page and not from an handler
 *
 * By order :
 *   - if the Get parameter 'module' refer to a tag associated to a module entry of the given parcours, and if its activities
 *   contains the current page, return this module
 *   - if not, return false
 *   - if there is at least one module in the given parcours which contains the current page, return it
 *   - if not, return false
 *
 * @param $parcours array The given 'parcours' entry
 * @return The module entry
 */
function getContextualModule($parcours){

    // if an handler is after the page tag in the wiki parameter variable, get only the tag
    $currentPage =  isset($_GET['wiki']) ?
        strpos($_GET['wiki'], '/') ? substr($_GET['wiki'], 0, strpos($_GET['wiki'], '/')) : $_GET['wiki'] :
        '';
    $moduleTag = isset($_GET['module']) ? $_GET['module'] : '';

    if (!empty($moduleTag)) {
        $moduleEntry = baz_valeurs_fiche($moduleTag);
        if ($moduleEntry && $moduleEntry['id_typeannonce'] == $GLOBALS['wiki']->config['lms_config']['module_form_id']
                && in_array($currentPage, explode(',', $moduleEntry['checkboxfiche' . $GLOBALS['wiki']->config['lms_config']['activite_form_id']])))
            return $moduleEntry;
    } else {
        foreach (explode(',', $parcours['checkboxfiche' . $GLOBALS['wiki']->config['lms_config']['module_form_id']]) as $currentModuleTag){
            $currentModuleEntry = baz_valeurs_fiche($currentModuleTag);
            if (in_array($currentPage, explode(',', $currentModuleEntry['checkboxfiche' . $GLOBALS['wiki']->config['lms_config']['activite_form_id']])))
                return $currentModuleEntry;
        }
    }
    return false;
}
