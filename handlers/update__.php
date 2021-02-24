<?php
/**
 * Handler called after the 'update' handler. Check if the different parts of the LMS module exists, and install the
 * needed ones.
 *
 * @category YesWiki
 * @package  lms-sso
 * @author   Adrien Cheype <adrien.cheype@gmail.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

namespace YesWiki;

use YesWiki\Core\Service\Performer;
use YesWiki\Lms\Service\LearnerManager;

// Verification de securite
if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

/**
 * Constants to define the OuiNon Lms list
 */
!defined('OUINON_LIST_JSON') && define(
    'OUINON_LIST_JSON',
    '{"label":{"oui":"Oui","non":"Non"},"titre_liste":"OuiNon Lms"}'
);

/**
 * Constants to define the contents of the LMS forms
 */
!defined('ACTIVITY_FORM_NAME') && define('ACTIVITY_FORM_NAME', 'LMS Activité');
!defined('ACTIVITY_FORM_DESCRIPTION') && define(
    'ACTIVITY_FORM_DESCRIPTION',
    'Activité (fiche de cours, exercice, vidéo, fiche pdf...) utilisée pour le module d\'apprentissage en ligne'
);
!defined('ACTIVITY_FORM_TEMPLATE') && define('ACTIVITY_FORM_TEMPLATE', 'texte***bf_titre***Titre de l\'activité***255***255*** *** ***text***1*** *** *** *** *** *** ***
tags***bf_autrices***Auteur·ice·s*** *** *** *** *** ***0*** ***Appuyer sur la touche « Entrée » pour séparer les auteur·ice·s
texte***bf_duree***Durée estimée de l\'activité en minutes*** *** *** *** ***number***0*** *** *** *** *** *** ***
texte***bf_licence***Licence*** *** *** *** ***text***0*** *** *** *** *** *** ***
textelong***bf_contenu***Contenu***80***40*** *** ***wiki***1*** *** *** *** *** *** ***
tags***bf_tags***Tags de description*** *** *** *** *** ***0*** ***Appuyer sur la touche « Entrée » pour séparer les mots-clés
liste***ListeOuinonLms***Activer les commentaires ?*** *** ***oui***bf_commentaires*** ***0*** *** *** * *** * *** *** ***
liste***ListeOuinonLms***Activer les réactions ?*** *** ***oui***bf_reactions*** ***0*** *** *** * *** * *** *** ***
reactions***reactions*** *** *** *** *** *** *** *** ***
navigationactivite***bf_navigation*** *** *** *** *** *** *** *** ***
acls*** + ***@admins***@admins*** *** *** *** *** *** ***');

!defined('MODULE_FORM_NAME') && define('MODULE_FORM_NAME', 'LMS Module');
!defined('MODULE_FORM_DESCRIPTION') && define(
    'MODULE_FORM_DESCRIPTION',
    'Module (enchaînement d\'activités) utilisé pour le module d\'apprentissage en ligne'
);
!defined('MODULE_FORM_TEMPLATE') && define('MODULE_FORM_TEMPLATE', 'texte***bf_titre***Titre du module***255***255*** *** ***text***1*** *** *** *** *** *** ***
textelong***bf_description***Description***80***4*** *** ***wiki***0*** *** *** *** *** *** ***
image***bf_image***Image***300***300***600***600***left***0*** ***
jour***bf_date_ouverture***Date d\'ouverture*** *** *** *** *** ***0*** *** *** *** *** *** ***
liste***ListeOuinonLms***Activé*** *** ***oui***bf_actif*** ***0*** *** *** *** *** *** ***
checkboxfiche***' . $GLOBALS['wiki']->config['lms_config']['activity_form_id'] . '***Activités*** *** *** ***bf_activites***dragndrop***0*** ***L\'ordre des activités définit la séquence d\'apprentissage du module*** *** *** *** ***
navigationmodule***bf_navigation*** *** *** *** *** *** *** *** ***
acls*** + ***@admins***@admins*** *** *** *** *** *** ***');

!defined('COURSE_FORM_NAME') && define('COURSE_FORM_NAME', 'LMS Parcours');
!defined('COURSE_FORM_DESCRIPTION') && define(
    'COURSE_FORM_DESCRIPTION',
    'Parcours (enchaînement de modules) utilisé pour le module d\'apprentissage en ligne'
);
!defined('COURSE_FORM_TEMPLATE') && define('COURSE_FORM_TEMPLATE', 'texte***bf_titre***Titre du parcours***255***255*** *** ***text***1*** *** *** *** *** *** ***
textelong***bf_description***Description***80***4*** *** ***wiki***0*** *** *** *** *** *** ***
checkboxfiche***' . $GLOBALS['wiki']->config['lms_config']['module_form_id'] . '***Modules*** *** *** ***bf_modules***dragndrop***0*** ***L\'ordre des modules définit le parcours de l\'apprenant*** *** *** *** ***
liste***ListeOuinonLms***Scénarisation des activités*** *** ***oui***bf_scenarisation_activites*** ***1*** ***Pour valider un module, un apprenant doit avoir valider toutes les activités du module*** *** *** *** ***
liste***ListeOuinonLms***Scénarisation des modules*** *** ***non***bf_scenarisation_modules*** ***1*** ***Si désactivé, les apprenants n\'ont pas besoin de terminer le module précédent pour accéder au suivant*** *** *** *** ***
acls*** + ***@admins***@admins*** *** *** *** *** *** ***');

/**
 * Check if a form exists and if not, add it to the nature table
 * @param $plugin_output_new the buffer in which to write
 * @param $formId the ID of the form
 * @param $formName the name of the form
 * @param $formeDescription the description of the form
 * @param $formTemplate the template which describe the fields of the form
 */
function checkAndAddForm(&$plugin_output_new, $formId, $formName, $formeDescription, $formTemplate)
{
    // test if the activite form exists, if not, install it
    $result = $GLOBALS['wiki']->Query('SELECT 1 FROM ' . $GLOBALS['wiki']->config['table_prefix'] . 'nature WHERE bn_id_nature = '
        . $formId . ' LIMIT 1');
    if (mysqli_num_rows($result) == 0) {
        $plugin_output_new .= 'ℹ️ Adding <em>' . $formName . '</em> form into <em>' . $GLOBALS['wiki']->config['table_prefix']
            . 'nature</em> table.<br />';

        $GLOBALS['wiki']->Query('INSERT INTO ' . $GLOBALS['wiki']->config['table_prefix'] . 'nature (`bn_id_nature` ,`bn_ce_i18n` ,'
            . '`bn_label_nature` ,`bn_template` ,`bn_description` ,`bn_sem_context` ,`bn_sem_type` ,`bn_sem_use_template` ,'
            . '`bn_condition`)'
            . ' VALUES (' . $formId . ', \'fr-FR\', \'' . mysqli_real_escape_string(
                $GLOBALS['wiki']->dblink,
                $formName
            ) . '\', \''
            . mysqli_real_escape_string($GLOBALS['wiki']->dblink, $formTemplate) . '\', \''
            . mysqli_real_escape_string($GLOBALS['wiki']->dblink, $formeDescription) . '\', \'\', \'\', 1, \'\')');

        $plugin_output_new .= '✅ Done !<br />';
    } else {
        $plugin_output_new .= '✅ The <em>' . $formName . '</em> form already exists in the <em>' .
            $GLOBALS['wiki']->config['table_prefix'] . 'nature</em> table.<br />';
    }
}

$learnerManager = $GLOBALS['wiki']->services->get(LearnerManager::class);
// the current learner
$learner = $learnerManager->getLearner();

$output = '';
if ($learner && $learner->isAdmin()) {
    $output .= '<strong>Extension LMS</strong><br/>';

    // if the OuiNon Lms list doesn't exist, create it
    if (!$this->LoadPage('ListeOuinonLms')) {
        $output .= 'ℹ️ Adding the <em>OuiNon Lms</em> list<br />';
        // save the page with the list value
        $this->SavePage('ListeOuinonLms', OUINON_LIST_JSON);
        // in case, there is already some triples for 'ListOuinonLms', delete them
        $this->DeleteTriple('ListeOuinonLms', 'http://outils-reseaux.org/_vocabulary/type', null);
        // create the triple to specify this page is a list
        $this->InsertTriple('ListeOuinonLms', 'http://outils-reseaux.org/_vocabulary/type', 'liste', '', '');
        $output .= '✅ Done !<br />';
    } else {
        $output .= '✅ The <em>Ouinon Lms</em> list already exists.<br />';
    }

    // test if the activite form exists, if not, install it
    checkAndAddForm(
        $output,
        $GLOBALS['wiki']->config['lms_config']['activity_form_id'],
        ACTIVITY_FORM_NAME,
        ACTIVITY_FORM_DESCRIPTION,
        ACTIVITY_FORM_TEMPLATE
    );
    // test if the module form exists, if not, install it
    checkAndAddForm(
        $output,
        $GLOBALS['wiki']->config['lms_config']['module_form_id'],
        MODULE_FORM_NAME,
        MODULE_FORM_DESCRIPTION,
        MODULE_FORM_TEMPLATE
    );
    // test if the course form exists, if not, install it
    checkAndAddForm(
        $output,
        $GLOBALS['wiki']->config['lms_config']['course_form_id'],
        COURSE_FORM_NAME,
        COURSE_FORM_DESCRIPTION,
        COURSE_FORM_TEMPLATE
    );

    // if the PageMenuLms page doesn't exist, create it with a default version
    if (!$this->LoadPage('PageMenuLms')) {
        $output .= 'ℹ️ Adding the <em>PageMenuLms</em> page<br />';
        $this->SavePage(
            'PageMenuLms',
            '""<div><span>""{{button link="config/root_page" nobtn="1" icon="fas fa-home"}}""'
            . '</span><span style="float: right;">""'
            . '{{button link="UserEntries" nobtn="1" text="Accès à mes fiches" icon="far fa-clone"></i>"}}""</span></div>""'
            . "\n\n" . '{{coursemenu}}'
        );
        $output .= '✅ Done !<br />';
    } else {
        $output .= '✅ The <em>PageMenuLms</em> page already exists.<br />';
    }
    $output .= '<hr />';

    // if the ProgressDashboard page doesn't exist, create it with a default version
    if (!$this->LoadPage('ProgressDashboard')) {
        $output .= 'ℹ️ Adding the <em>ProgressDashboard</em> page<br />';
        $this->SavePage(
            'ProgressDashboard',
            '{{progressdashboard}}'
        );
        $output .= '✅ Done !<br />';
    } else {
        $output .= '✅ The <em>ProgressDashboard</em> page already exists.<br />';
    }
    $output .= '<hr />';

    // if the LearnerDashboard page doesn't exist, create it with a default version
    if (!$this->LoadPage('LearnerDashboard')) {
        $output .= 'ℹ️ Adding the <em>LearnerDashboard</em> page<br />';
        $this->SavePage(
            'LearnerDashboard',
            '{{learnerdashboard}}'
        );
        $output .= '✅ Done !<br />';
    } else {
        $output .= '✅ The <em>LearnerDashboard</em> page already exists.<br />';
    }
    $output .= '<hr />';
}


// update old 500* forms to new 120* forms
// TODO : in a few releases delete this
// activity_form_id: 1201
$activities = $this->LoadAll('SELECT * FROM `'.$this->config['table_prefix'].'pages` WHERE body LIKE \'%"id_typeannonce":"5001"%\'');
if ($GLOBALS['wiki']->config['lms_config']['course_form_id'] != '5001' && count($activities)>0) {
    $output .= 'ℹ️ changing activities id from 5001 to 1201<br />';
    $this->Query('UPDATE `'.$this->config['table_prefix'].'pages` SET BODY=REPLACE( `body` , \'"id_typeannonce":"5001"\', \'"id_typeannonce":"1201"\') WHERE body LIKE \'%"id_typeannonce":"5001"%\'');
    $this->Query('UPDATE `'.$this->config['table_prefix'].'pages` SET BODY=REPLACE( `body` , \'checkboxfiche5001\', \'checkboxfiche1201bf_activites\') WHERE 1');
    $this->Query('DELETE FROM `'.$this->config['table_prefix'].'nature` WHERE bn_id_nature="5001"');
    $output .= '✅ Done !<br />';
}
// module_form_id: 1202
$modules = $this->LoadAll('SELECT * FROM `'.$this->config['table_prefix'].'pages` WHERE body LIKE \'%"id_typeannonce":"5002"%\'');
if ($GLOBALS['wiki']->config['lms_config']['course_form_id'] != '5002' && count($modules)>0) {
    $output .= 'ℹ️ changing modules id from 5002 to 1202<br />';
    $this->Query('UPDATE `'.$this->config['table_prefix'].'pages` SET BODY=REPLACE( `body` , \'"id_typeannonce":"5002"\', \'"id_typeannonce":"1202"\') WHERE body LIKE \'%"id_typeannonce":"5002"%\'');
    $this->Query('UPDATE `'.$this->config['table_prefix'].'pages` SET BODY=REPLACE( `body` , \'checkboxfiche5002\', \'checkboxfiche1202bf_modules\') WHERE 1');
    $this->Query('DELETE FROM `'.$this->config['table_prefix'].'nature` WHERE bn_id_nature="5002"');
    $output .= '✅ Done !<br />';
}
// course_form_id: 1203
$courses = $this->LoadAll('SELECT * FROM `'.$this->config['table_prefix'].'pages` WHERE body LIKE \'%"id_typeannonce":"5003"%\'');
if ($GLOBALS['wiki']->config['lms_config']['course_form_id'] != '5003' && count($courses)>0) {
    $output .= 'ℹ️ changing courses id from 5003 to 1203<br />';
    $this->Query('UPDATE `'.$this->config['table_prefix'].'pages` SET BODY=REPLACE( `body` , \'"id_typeannonce":"5003"\', \'"id_typeannonce":"1203"\') WHERE body LIKE \'%"id_typeannonce":"5003"%\'');
    $this->Query('DELETE FROM `'.$this->config['table_prefix'].'nature` WHERE bn_id_nature="5003"');
    $output .= '✅ Done !<br />';
}

// Add button to return to previous page
$tag = $GLOBALS['wiki']->getPageTag();
$vars['link'] = $tag;
$vars['text'] = 'Retourner à la page ' . $tag;
$vars['title'] = 'Retourner à la page ' . $tag;
$vars['class'] = 'btn-success';
$output .= $GLOBALS['wiki']->services->get(Performer::class)->run('button', 'action', $vars);

// add the content before footer
$plugin_output_new = str_replace(
    '<!-- end handler /update -->',
    $output . '<!-- end handler /update -->',
    $plugin_output_new
);
