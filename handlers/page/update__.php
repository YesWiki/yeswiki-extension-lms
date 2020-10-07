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

// Verification de securite
if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

/**
 * Constants to define the OuiNon Lms list
 */
!defined('OUINON_LIST_JSON') && define('OUINON_LIST_JSON', '{"label":{"oui":"Oui","non":"Non"},"titre_liste":"OuiNon Lms"}');

/**
 * Constants to define the contents of the LMS forms
 */
!defined('ACTIVITE_FORM_NOM') && define('ACTIVITE_FORM_NOM', 'Activité LMS');
!defined('ACTIVITE_FORM_DESCRIPTION') && define('ACTIVITE_FORM_DESCRIPTION', 'Activité (fiche de cours, exercice, vidéo, fiche pdf...) utilisée pour le module d\'apprentissage en ligne');
!defined('ACTIVITE_FORM_TEMPLATE') && define('ACTIVITE_FORM_TEMPLATE', 'texte***bf_titre***Titre de l\'activité***255***255*** *** ***text***1*** *** *** *** *** *** ***
tags***bf_autrices***Auteur·ice·s*** *** *** *** *** ***0*** ***Appuyer sur la touche « Entrée » pour séparer les auteur·ice·s
texte***bf_duree***Durée estimée de l\'activité en minutes*** *** *** *** ***text***0*** *** *** *** *** *** ***
texte***bf_licence***Licence*** *** *** *** ***text***0*** *** *** *** *** *** ***
textelong***bf_contenu***Contenu***80***40*** *** ***wiki***1*** *** *** *** *** *** ***
tags***bf_tags***Tags de description*** *** *** *** *** ***0*** ***Appuyer sur la touche « Entrée » pour séparer les mots-clés
navigationactivite***bf_navigation*** *** *** *** *** *** *** *** ***
acls*** * ***@admins***@admins*** *** *** *** *** *** ***
reactions***reactions*** *** *** *** *** *** *** *** ***');

!defined('MODULE_FORM_NOM') && define('MODULE_FORM_NOM', 'Module LMS');
!defined('MODULE_FORM_DESCRIPTION') && define('MODULE_FORM_DESCRIPTION', 'Module (enchaînement d\'activités) utilisé pour le module d\'apprentissage en ligne');
!defined('MODULE_FORM_TEMPLATE') && define('MODULE_FORM_TEMPLATE', 'texte***bf_titre***Titre du module***255***255*** *** ***text***1*** *** *** *** *** *** ***
textelong***bf_description***Description***80***4*** *** ***wiki***0*** *** *** *** *** *** ***
image***bf_image***Image***300***300***600***600***left***0*** ***
texte***bf_duree***Durée estimée*** *** *** *** ***text***0*** *** *** *** *** *** ***
jour***bf_date_ouverture***Date d\'ouverture*** *** *** *** *** ***0*** *** *** *** *** *** ***
liste***ListeOuinonLms***Activé*** *** ***oui***bf_active*** ***0*** *** ***@admins*** *** *** ***
checkboxfiche***5001***Activités*** *** *** *** ***tags***0*** ***L\'ordre des activités définit la séquence d\'apprentissage du module***@admins*** *** *** ***
navigationmodule***bf_navigation*** *** *** *** *** *** *** *** ***
acls***+***@admins***@admins*** *** *** *** *** *** ***');

!defined('PARCOURS_FORM_NOM') && define('PARCOURS_FORM_NOM', 'Parcours LMS');
!defined('PARCOURS_FORM_DESCRIPTION') && define('PARCOURS_FORM_DESCRIPTION', 'Parcours (enchaînement de modules) utilisé pour le module d\'apprentissage en ligne');
!defined('PARCOURS_FORM_TEMPLATE') && define('PARCOURS_FORM_TEMPLATE', 'texte***bf_titre***Titre du parcours***255***255*** *** ***text***1*** *** *** *** *** *** ***
checkboxfiche***5002***Modules*** *** *** *** ***tags***0*** ***L\'ordre des modules définit le parcours de l\'apprenant*** *** *** *** ***
liste***ListeOuinonLms***Scénarisation des activités*** *** ***oui***bf_scenarisation_activites*** ***1*** ***Pour valider un module  un apprenant doit avoir valider toutes les activités du module***@admins*** *** *** ***
liste***ListeOuinonLms***Accès libre aux modules*** *** ***non***bf_modules_libres*** ***1*** ***Si oui  les apprenants n\'ont pas besoin de terminer le module précédent pour accéder au suivant***@admins*** *** *** ***
acls***+***@admins***@admins*** *** *** *** *** *** ***');

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
            . ' VALUES (' . $formId . ', \'fr-FR\', \'' . mysqli_real_escape_string($GLOBALS['wiki']->dblink, $formName) . '\', \''
            . mysqli_real_escape_string($GLOBALS['wiki']->dblink, $formTemplate) . '\', \''
            . mysqli_real_escape_string($GLOBALS['wiki']->dblink, $formeDescription) . '\', \'\', \'\', 1, \'\')');

        $plugin_output_new .= '✅ Done !<br />';
    } else {
        $plugin_output_new .= '✅ The <em>' . $formName . '</em> form already exists in the <em>' .
            $GLOBALS['wiki']->config['table_prefix'] . 'nature</em> table.<br />';
    }
}

if ($this->UserIsAdmin()) {

    // get the content of the page generated by the update handler and remove the footer
    $pattern = '~^(.*)' . preg_quote($this->footer()) . '$~mis';
    $matches = [];
    $plugin_output_new = preg_match($pattern, $plugin_output_new, $matches) ? $matches[1] : $plugin_output_new;

    $plugin_output_new .= '<strong>Extension LMS</strong><br/>';

    // if the OuiNon Lms list doesn't exist, create it
    if (!$this->LoadPage('ListeOuinonLms')){
        $plugin_output_new .= 'ℹ️ Adding the <em>OuiNon Lms</em> list<br />';
        // save the page with the list value
        $this->SavePage('ListeOuinonLms', OUINON_LIST_JSON);
        // in case, there is already some triples for 'ListOuinonLms', delete them
        $this->DeleteTriple('ListeOuinonLms', 'http://outils-reseaux.org/_vocabulary/type', null);
        // create the triple to specify this page is a list
        $this->InsertTriple('ListeOuinonLms', 'http://outils-reseaux.org/_vocabulary/type', 'liste', '', '');
        $plugin_output_new .= '✅ Done !<br />';
    } else {
        $plugin_output_new .= '✅ The <em>Ouinon Lms</em> list already exists.<br />';
    }

    // test if the activite form exists, if not, install it
    checkAndAddForm($plugin_output_new, $GLOBALS['wiki']->config['lms_config']['activite_form_id'], ACTIVITE_FORM_NOM, ACTIVITE_FORM_DESCRIPTION, ACTIVITE_FORM_TEMPLATE);
    // test if the module form exists, if not, install it
    checkAndAddForm($plugin_output_new, $GLOBALS['wiki']->config['lms_config']['module_form_id'], MODULE_FORM_NOM, MODULE_FORM_DESCRIPTION, MODULE_FORM_TEMPLATE);
    // test if the parcours form exists, if not, install it
    checkAndAddForm($plugin_output_new, $GLOBALS['wiki']->config['lms_config']['parcours_form_id'], PARCOURS_FORM_NOM, PARCOURS_FORM_DESCRIPTION, PARCOURS_FORM_TEMPLATE);

    // if the PageMenuLms page doesn't exist, create it with a default version
    if (!$this->LoadPage('PageMenuLms')) {
        $plugin_output_new .= 'ℹ️ Adding the <em>PageMenuLms</em> page<br />';
        $this->SavePage('PageMenuLms', '""<div><span>""{{button link="PagePrincipale" nobtn="1" icon="fas fa-home"}}""</span><span style="float: right;">""{{button link="UserEntries" nobtn="1" text="Accès à mes fiches" icon="far fa-clone"></i>"}}""</span></div>""'
            . "\n\n" . '{{menuparcours}}');
        $plugin_output_new .= '✅ Done !<br />';
    } else {
        $plugin_output_new .= '✅ The <em>PageMenuLms</em> page already exists.<br />';
    }

    // Structure de répertoire désirée
    $customBazarTemplateDir = 'templates/bazar/templates/';
    if (!is_dir($customBazarTemplateDir)) {
        if (!mkdir($customBazarTemplateDir, 0777, true)) {
            die('Echec lors de la création des répertoires...');
        } else {
            $plugin_output_new .= "ℹ ️Creating the folder <em>$customBazarTemplateDir</em> for bazar templates<br/>✅Done !<br />";
        }
    } else {
        $plugin_output_new .= "✅ The folder <em>$customBazarTemplateDir</em> for bazar templates exists.<br />";
    }

    $destFile = $customBazarTemplateDir.'fiche-' . $GLOBALS['wiki']->config['lms_config']['activite_form_id'] . '.tpl.html';
    if (!file_exists($destFile)){
        $plugin_output_new .= "ℹ ️Copying the <em>Activité</em> bazar template to <em>$destFile</em>. Don't forget to launch "
            . "again this update page if this form ID has changed !<br/>";
        copy('tools/lms/libs/bazar-templates/fiche-5001.tpl.html', $destFile);
        $plugin_output_new .= '✅ Done !<br />';
    } else {
        $plugin_output_new .= "✅ The <em>Activité</em> bazar template (<em>$destFile</em>)  already exists.<br />";
    }

    $destFile = $customBazarTemplateDir.'fiche-' . $GLOBALS['wiki']->config['lms_config']['module_form_id'] . '.tpl.html';
    if (!file_exists($destFile)){
        $plugin_output_new .= "ℹ️ Copying the <em>Module</em> bazar template to <em>$destFile</em>. Don't forget to launch "
            . "again this update page if this form ID has changed !<br/>";
        copy('tools/lms/libs/bazar-templates/fiche-5002.tpl.html', $destFile);
        $plugin_output_new .= '✅ Done !<br />';
    } else {
        $plugin_output_new .= "✅ The <em>Module</em> bazar template (<em>$destFile</em>)  already exists.<br />";
    }

    $plugin_output_new .= '<hr />';

    // add the footer again
    $plugin_output_new .= $this->footer();
}
