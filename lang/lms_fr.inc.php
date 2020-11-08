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
        'LMS_MENUPARCOURS_ERROR' => 'Erreur détectée dans l\'action menuparcours du module LMS : ',
        'LMS_MODULE_NOACCESS' => 'Vous n\'avez pas accès pour l\'instant à ce module',
        'LMS_MODULE_NOACCESS_ADMIN' => 'Les apprenants n\'ont pas accès à ce module',
        'LMS_MODULE_PREVIOUS' => 'Module précédent',
        'LMS_MODULE_NEXT' => 'Module suivant',
        'LMS_PREVIOUS' => 'Précédent',
        'LMS_NEXT' => 'Suivant',
        'LMS_BEGIN' => 'Commencer',
        'LMS_BEGIN_NOACCESS_ADMIN' => 'Accès administrateur',
        'LMS_VIDEO_PARAM_ERROR' => 'L\'action video doit être appelée avec les paramètres « id » et « serveur ». Pour « serveur », seules les valeurs « vimeo » ou « youtube » sont acceptées.',
        'LMS_PDF_PARAM_ERROR' => 'L\'action pdf doit être appelée avec le paramètre « url » et l\'url renseignée doit provenir de la même origine que le wiki : c\'est à dire du même sous-domaine du serveur (par exemple \'xxx.yyy.com\'), du même schéma (par exemple \'https\') et du même port s\'il est spécifié (par exemple \'8080\').',
        'LMS_RETURN_BUTTON' => 'Retour',
    )
);
