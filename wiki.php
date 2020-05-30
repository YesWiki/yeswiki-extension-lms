<?php
/**
 * Basic config of the lms module
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

// Constants
define('LMS_PATH', 'tools/lms/');

// Includes
require_once LMS_PATH . 'libs/bazarlms.fonct.inc.php';

if (empty($wakkaConfig['lms_config'])) {
    $wakkaConfig['lms_config'] = [
        'activite_form_id' => 5001,
        'module_form_id' => 5002,
        'parcours_form_id' => 5003,
    ];
}