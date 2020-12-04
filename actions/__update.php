<?php
/**
 * Action called before the 'update' action. The aim is to test is update is needed
 *
 * @category YesWiki
 * @author   Jérémy Dufraisse
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */
namespace YesWiki;

use YesWiki\Core\Service\Performer;

/**
 * Check if a form exists 
 * @param $formId the ID of the form
 */
function checkForm($formId)
{
    // test if the activite form exists, if not, install it
    $result = $GLOBALS['wiki']->Query('SELECT 1 FROM ' . $GLOBALS['wiki']->config['table_prefix'] . 'nature WHERE bn_id_nature = '
        . $formId . ' LIMIT 1');
    return !(mysqli_num_rows($result) == 0) ;
}

if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

if ($this->UserIsAdmin() && !isset($get['upgrade']) && !isset($get['delete']) 
            && (!$this->LoadPage('PageMenuLms') || !$this->LoadPage('ListeOuinonLms') 
            || !checkForm($GLOBALS['wiki']->config['lms_config']['activite_form_id'])
            || !checkForm($GLOBALS['wiki']->config['lms_config']['module_form_id'])
            || !checkForm($GLOBALS['wiki']->config['lms_config']['parcours_form_id']))) {
    $html_return = '<div class="well">Le module LMS doit encore configurer quelques éléments. Cliquez sur le bouton suivant pour terminer l\'installation.' ;
    $vars['link'] = $GLOBALS['wiki']->Href('update') ;
    $vars['text'] = 'Terminer la mise à jour' ;
    $vars['title'] = 'Terminer la mise à jour' ;
    $vars['class'] = 'btn-danger' ;
    $html_return .= $this->services->get(Performer::class)->run('button', 'action', $vars) ;
    $html_return .= '</div>' ;
    echo $html_return ;
}