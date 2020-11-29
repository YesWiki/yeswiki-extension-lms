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
use YesWiki\Bazar\Service\FicheManager;

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
    $parcoursTag = empty($_GET['parcours']) ? '' : $_GET['parcours'];
    if (!empty($parcoursTag)) {
        $parcoursEntry = $GLOBALS['wiki']->services->get(FicheManager::class)->getOne($parcoursTag);
        if ($parcoursEntry && $parcoursEntry['id_typeannonce'] == $GLOBALS['wiki']->config['lms_config']['parcours_form_id'])
            return $parcoursEntry;
    } else {
        $entries = $GLOBALS['wiki']->services->get(FicheManager::class)->search(['formsIds' => [$GLOBALS['wiki']->config['lms_config']['parcours_form_id']]]);
        if (!empty($entries)) {
            return reset($entries);
        }
    }
    return false;
}

/**
 * Get the contextual module according to the given 'parcours' entry, its modules, the Get parameter 'module' and the current page.
 *
 * By order :
 *   - if the Get parameter 'module' refer to a tag associated to a module entry of the given parcours, and if its activities
 *   contains the current page, return this module
 *   - if not, return false
 *   - if the current page refers to a module which is contained by the given parcours, return it
 *   - or if the current page refers to an activity and there is at least one module in the given parcours which contains the
 *   current page, return it
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

    $currentPageModule = $currentPage;
    if ($GLOBALS['wiki']->config['lms_config']['use_tabs']) {
        // if a number is at the end of the page tag, it means that it's a tab page corresponding to the page without the number
        // thus, to associate this tab page to its parent one, we remove the number from the page tag
        $currentPage = preg_replace('/[0-9]*$/', '', $currentPage);
    }
    $moduleTag = isset($_GET['module']) ? $_GET['module'] : '';

    if (!empty($moduleTag)) {
        $moduleEntry = $GLOBALS['wiki']->services->get(FicheManager::class)->getOne($moduleTag);

        if ($moduleEntry && intval($moduleEntry['id_typeannonce']) == $GLOBALS['wiki']->config['lms_config']['module_form_id']
                && in_array($currentPage, explode(',', $moduleEntry['checkboxfiche'
                    . $GLOBALS['wiki']->config['lms_config']['activite_form_id'] . 'bf_activites']))) {
                    return $moduleEntry;
                } else {
                    return false;
                }
    } else {
        $currentPageEntry = $GLOBALS['wiki']->services->get(FicheManager::class)->getOne($currentPageModule);
        if ($currentPageEntry && $currentPageEntry['id_typeannonce'] == $GLOBALS['wiki']->config['lms_config']['module_form_id']
                && in_array($currentPageModule, explode(',', $parcours['checkboxfiche'
                    . $GLOBALS['wiki']->config['lms_config']['module_form_id'] . 'bf_modules'])))
            return $currentPageEntry;

        foreach (explode(',', $parcours['checkboxfiche' . $GLOBALS['wiki']->config['lms_config']['module_form_id']
            . 'bf_modules']) as $currentModuleTag){
            $currentModuleEntry = $GLOBALS['wiki']->services->get(FicheManager::class)->getOne($currentModuleTag);
            if (in_array($currentPage, explode(',', $currentModuleEntry['checkboxfiche'
                . $GLOBALS['wiki']->config['lms_config']['activite_form_id'] . 'bf_activites'])))
                return $currentModuleEntry;
        }
    }
    return false;
}

/**
 * Helper to get previous value of an array
 *
 * @param string $needle The key to search
 * @param array $haystack The array
 * @return mixed The previous value or false if there is no previous value
 */
function getPrevValue($needle, $haystack = array()){
    $found_index = array_search($needle, $haystack);
    if ($found_index === false || $found_index === 0) {
        return false;
    }
    return $haystack[$found_index-1];
}
  
function getAllReactions($pageTag, $ids, $user){
    $res = ['reactions' => [], 'userReaction' => ''];
    // initialise empty reactions
    foreach ($ids as $id) {
          $res['reactions'][$id]= 0;
    }
    // get reactions in db
    $val = $GLOBALS['wiki']->getAllTriplesValues($pageTag, 'https://yeswiki.net/vocabulary/reaction', '', '');
    foreach ($val as $v) {
        $v = json_decode($v['value'], true);
        if (count($v)>0) {
            if ($v['user'] == $user ) {
                $res['userReaction'] = $v['id'];
            }
            // check for existance of reaction
            if (isset($res['reactions'][$v['id']])) {
                $res['reactions'][$v['id']]++;
            }
        }
    }
    return $res;
}

function getUserReactionOnPage($pageTag, $user){
    $res = '';
    // get reactions in db
    $val = $GLOBALS['wiki']->getAllTriplesValues($pageTag, 'https://yeswiki.net/vocabulary/reaction', '', '');
    foreach ($val as $v) {
        $v = json_decode($v['value'], true);
        if (!empty($v)) {
            $res = $v;
        }
    }
    return $res;
}
