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

// Constants
!defined('LMS_PATH') && define('LMS_PATH', 'tools/lms/');

// Includes
require_once __DIR__ . '/libs/CourseStructure.php';
require_once __DIR__ . '/libs/Activity.php';
require_once __DIR__ . '/libs/Module.php';
require_once __DIR__ . '/libs/Course.php';
require_once __DIR__ . '/libs/Learner.php';
require_once __DIR__ . '/libs/TimeLogs.php'; // to require before following lines
require_once __DIR__ . '/libs/ExtraActivityLog.php';
require_once __DIR__ . '/libs/ExtraActivityLogs.php';
require_once __DIR__ . '/libs/Progresses.php';
require_once __DIR__ . '/libs/ConditionsState.php';
