<?php
/**
 * French translation
 *
 * @category YesWiki
 * @package  lms
 * @author   Adrien Cheype <adrien.cheype@gmail.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

$GLOBALS['translations'] = array_merge(
    $GLOBALS['translations'],
    array(
        'LMS_COURSEMENU_ERROR' => 'Erreur détectée dans l\'action {{coursemenu ...}} du module LMS : ',
        //'LMS_MODULE_NOACCESS' => 'Vous n\'avez pas accès pour l\'instant à ce module',
        //'LMS_MODULE_NOACCESS_ADMIN' => 'Les apprenants n\'ont pas accès à ce module',
        'LMS_MODULE_PREVIOUS' => 'Module précédent',
        'LMS_MODULE_NEXT' => 'Module suivant',
        'LMS_PREVIOUS' => 'Précédent',
        'LMS_NEXT' => 'Suivant',
        'LMS_BEGIN' => 'Commencer',
        'LMS_BEGIN_ONLY_ADMIN' => 'Accès admin',
        'LMS_RESUME' => 'Reprendre',
        'LMS_RETURN_BUTTON' => 'Retour',
        'LMS_ADMIN_NO_MODULES' => 'Pas encore de modules associés à ce parcours, créez d\'abord des activités, puis des modules avec des activités dedans, puis associez les modules au parcours en l\'éditant à nouveau',
        'LMS_NO_MODULES' => 'Ce parcours ne contient pas encore de modules',
        'LMS_ACTIVITY_SCENARISATION' => 'Scénarisation des activités',
        'LMS_MODULE_SCENARISATION' => 'Scénarisation des modules',
        'LMS_OPEN_MODULE' => 'Ce module est accessible',
        'LMS_MODULE_WILL_OPEN' => 'Ce module sera accessible dans',
        'LMS_SINCE' => 'depuis',
        'LMS_CLOSED_MODULE' => 'Ce module est fermé',
        'LMS_ACTIVITY_NOT_ACCESSIBLE' => 'Vous n\'avez pas accès à cette activité',
        'LMS_MODULE_NOT_ACCESSIBLE' => 'Ce module n\'est pas accessible sans finir les modules précédents',
        'LMS_UNKNOWN_STATUS_MODULE' => 'Ce module n\'est pas dans un parcours ou ne comporte aucune activité',
        'LMS_RESERVED_FOR_ADMINS' => 'Informations pour les administrateurs',
        'LMS_DISPLAY_MENU' => 'Menu',
        'LMS_ACTIVITY' => 'Activité',
        'LMS_ACTIVITIES' => 'Activités',
        'LMS_MODULE' => 'Module',
        'LMS_MODULES' => 'Modules',
        'LMS_COURSE' => 'Parcours',
        'LMS_ESTIMATED_TIME' => 'Temps estimé',
        'LMS_PROGRESS_DASHBOARD' => 'Tableau de bord des progressions',
        'LMS_ACTIVITY_PREVIEW' => 'Prévisualiser l\'activité',
        'LMS_MODULE_PREVIEW' => 'Prévisualiser le module',
        'LMS_COURSE_PREVIEW' => 'Prévisualiser le parcours',
        'LMS_LEARNER_PREVIEW' => 'Prévisualiser le profil de l\'apprenant⋅e',
        'LMS_VIEW_PROGRESS_DASHBOARD' => 'Tableau de bord',
        'LMS_VIEW_MODULE_PROGRESSES' => 'Voir les progressions du module',
        'LMS_BACK_TO_COURSE' => 'Retour au parcours',
        'LMS_BACK_TO_COURSE_PROGRESSES' => 'Retour aux progressions du parcours',
        'LMS_VIEW_LEARNER_DASHBOARD' => 'Voir le détail de l\'activité de l\'apprenant',
        'LMS_FINISHED_RATIO' => 'Apprenant⋅e⋅s qui ont terminé⋅e⋅s / Nombre d\'apprenant⋅e⋅s',
        'LMS_ESTIMATED_TIME_DETAILLED' => 'Temps estimé par les formateurs',
        'LMS_FINISHED_LEARNERS' => 'Apprenant⋅e⋅s qui ont terminé⋅e⋅s',
        'LMS_NOT_FINISHED_LEARNERS' => 'Apprenant⋅e⋅s qui n\'ont pas terminé⋅e⋅s',
        'LMS_ERROR_NOT_A_VALID_MODULE' => 'Le module donné en paramètre n\'existe pas ou n\'appartient pas au parcours',

        // For Learner Dashboard
        'LMS_DASHBOARD' => 'Tableau de bord de ',
        'LMS_DASHBOARD_TYPE' => 'Type',
        'LMS_DASHBOARD_NAME' => 'Nom',
        'LMS_DASHBOARD_PROGRESS' => 'Avancement',
        'LMS_DASHBOARD_ELAPSEDTIME' => 'Temps passé',
        'LMS_DASHBOARD_FIRSTACCESS' => 'Premier accès',
        'LMS_DASHBOARD_COURSE' => 'Parcours',
        'LMS_DASHBOARD_MODULE' => 'Module',
        'LMS_DASHBOARD_FINISHED_F' => 'Terminée',
        'LMS_DASHBOARD_IN_COURSE' => 'En cours',
        'LMS_DASHBOARD_SELECT_USER_TITLE' => 'Choix de l\'utilisateur',
        'LMS_DASHBOARD_EXPORT_TO_CSV' => 'Export CSV',
        'LMS_DASHBOARD_LEGEND' => 'Légende',
        'LMS_DASHBOARD_FILENAME' => 'tableau_de_bord',
        'LMS_DASHBOARD_RETURN' => 'Retourner à ',
        'LMS_NO_ACTIVITY' => 'Pas d\'activités',
        'LMS_MODULE_PREVIEW_NOT_POSSIBLE' => 'Non accessible ici tant que vous n\'avez pas commencé le module ',
        'LMS_ACTIVITY_PREVIEW_NOT_POSSIBLE' => 'Non accessible ici tant que vous n\'avez pas commencé l\'activité ',
        'LMS_LOGGED_USERS_ONLY_HANDLER' => 'Il faut être connecté pour pouvoir utiliser le handler',
        'LMS_UPDATE_ELAPSED_TIME_UPDATE' => 'Mettre à jour',
        'LMS_UPDATE_ELAPSED_TIME_MESSAGE' => 'Combien de temps avez-vous passé sur ',
        'LMS_UPDATE_ELAPSED_TIME_MODULE' => 'le module',
        'LMS_UPDATE_ELAPSED_TIME_ACTIVITY' => 'l\'activté',
        'LMS_UPDATE_ELAPSED_TIME_MINUTES' => 'minutes',

        // LMS Reactions
        'LMS_LOGIN_TO_REACT' => 'Pour réagir, identifiez-vous!',
        'LMS_SHARE_YOUR_REACTION' => 'Partagez votre réaction à propos de ce contenu',
        'LMS_TO_ALLOW_REACTION' => 'Pour vous permettre de réagir',
        'LMS_PLEASE_LOGIN' => 'veuillez vous identifier',
        'LMS_SHARE_YOUR_COMMENT' => 'Et n\'hésitez pas à faire un commentaire pour approfondir la réflexion !',

        // action importcourses
        'LMS_IMPORT_TOKEN' => 'Token du site distant',
        'LMS_IMPORT_URL' => 'URL à importer',
        'LMS_IMPORT' => 'Importer',
    )
);
