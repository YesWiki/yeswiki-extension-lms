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
!defined('LMS_PATH') && define('LMS_PATH', 'tools/lms/');

// Includes
require_once LMS_PATH . 'vendor/autoload.php';
require_once LMS_PATH . 'libs/bazarlms.fonct.inc.php';
// require_once LMS_PATH . 'libs/LmsActivity.class.php';
// require_once LMS_PATH . 'libs/LmsModule.class.php';
// require_once LMS_PATH . 'libs/LmsLearner.class.php';

if (!isset($wakkaConfig['lms_config'])) {
    $wakkaConfig['lms_config'] = [];
}
$wakkaConfig['lms_config']['use_tabs'] = empty($wakkaConfig['lms_config']['use_tabs']) ? true : $wakkaConfig['lms_config']['use_tabs'];
$wakkaConfig['lms_config']['display_activity_title'] = empty($wakkaConfig['lms_config']['display_activity_title']) ? true : $wakkaConfig['lms_config']['display_activity_title'];
$wakkaConfig['lms_config']['activite_form_id'] = empty($wakkaConfig['lms_config']['activite_form_id']) ? 5001 : $wakkaConfig['lms_config']['activite_form_id'];
$wakkaConfig['lms_config']['module_form_id'] = empty($wakkaConfig['lms_config']['module_form_id']) ? 5002 : $wakkaConfig['lms_config']['module_form_id'];
$wakkaConfig['lms_config']['parcours_form_id'] = empty($wakkaConfig['lms_config']['parcours_form_id']) ? 5003 : $wakkaConfig['lms_config']['parcours_form_id'];
$wakkaConfig['lms_config']['use_yeswiki_comments'] = empty($wakkaConfig['lms_config']['use_yeswiki_comments']) ? false : $wakkaConfig['lms_config']['use_yeswiki_comments'];

// $GLOBALS['lmsActivity'] = new YesWiki\LmsActivity($this);
// $GLOBALS['lmsModule'] = new YesWiki\LmsModule($this);
// $GLOBALS['lmsLearner'] = new YesWiki\LmsLearner($this);