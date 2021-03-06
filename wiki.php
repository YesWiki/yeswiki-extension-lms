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
require_once LMS_PATH . 'libs/CourseStructure.php';
require_once LMS_PATH . 'libs/Activity.php';
require_once LMS_PATH . 'libs/Module.php';
require_once LMS_PATH . 'libs/Course.php';
require_once LMS_PATH . 'libs/Learner.php';
require_once LMS_PATH . 'libs/TimeLogs.php'; // to require before following lines
require_once LMS_PATH . 'libs/ExtraActivityLog.php';
require_once LMS_PATH . 'libs/ExtraActivityLogs.php';
require_once LMS_PATH . 'libs/Progresses.php';
require_once LMS_PATH . 'libs/ConditionsState.php';
