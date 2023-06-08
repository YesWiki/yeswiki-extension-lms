<?php
/**
 * English translation
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
        'LMS_COURSEMENU_ERROR' => 'Error detected in the {{coursemenu ...}} action of the LMS module: ',
        //'LMS_MODULE_NOACCESS' => 'You do not have access to this module at the moment',
        //'LMS_MODULE_NOACCESS_ADMIN' => 'Learners do not have access to this module',
        'LMS_MODULE_PREVIOUS' => 'Previous module',
        'LMS_MODULE_NEXT' => 'Next module',
        'LMS_PREVIOUS' => 'Previous',
        'LMS_NEXT' => 'Next',
        'LMS_BEGIN' => 'Begin',
        'LMS_BEGIN_ONLY_ADMIN' => 'Admin access',
        'LMS_RESUME' => 'Resume',
        'LMS_RESTART' => 'See again',
        'LMS_RETURN_BUTTON' => 'Back',
        'LMS_ADMIN_NO_MODULES' => 'No modules associated to this course yet. Create activities first, then modules with activities in them, then associate the modules to the course by editing it again.',
        'LMS_NO_MODULES' => 'This course does not yet contain any modules.',
        'LMS_ACTIVITY_SCRIPTING' => 'Activities scripting',
        'LMS_MODULE_SCRIPTING' => 'Module scripting',
        'LMS_OPEN_MODULE' => 'This module is accessible',
        'LMS_MODULE_WILL_OPEN' => 'This module will be available in',
        'LMS_SINCE' => 'since',
        'LMS_CLOSED_MODULE' => 'This module is closed',
        'LMS_ACTIVITY_NOT_ACCESSIBLE' => 'You do not have access to this activity',
        'LMS_MODULE_NOT_ACCESSIBLE' => 'This module is not accessible without finishing the previous modules',
        'LMS_UNKNOWN_STATUS_MODULE' => 'This module is not in a course or does not include any activities',
        'LMS_RESERVED_FOR_ADMINS' => 'Information for administrators',
        'LMS_INDEX_MENU' => 'Index',
        'LMS_ACTIVITY' => 'Activity',
        'LMS_ACTIVITIES' => 'Activities',
        'LMS_MODULE' => 'Module',
        'LMS_MODULES' => 'Modules',
        'LMS_ESTIMATED_TIME' => 'Estimated time',

        // ProgressDashboard
        'LMS_PROGRESS_DASHBOARD' => 'Progress Dashboard',
        'LMS_ACTIVITY_PREVIEW' => 'Preview the activity',
        'LMS_MODULE_PREVIEW' => 'Preview the module',
        'LMS_COURSE_PREVIEW' => 'Preview the course',
        'LMS_LEARNER_PREVIEW' => 'Preview the learner profile',
        'LMS_VIEW_PROGRESS_DASHBOARD' => 'Dashboard',
        'LMS_VIEW_MODULE_PROGRESSES' => 'See the module progresses',
        'LMS_BACK_TO_COURSE' => 'Back to the course',
        'LMS_BACK_TO_COURSE_PROGRESSES' => 'Back to the course progresses',
        'LMS_VIEW_LEARNER_DASHBOARD' => 'See the details of the learner\'s activity',
        'LMS_FINISHED_RATIO' => 'Learners who have completed / Number of learners',
        'LMS_ESTIMATED_TIME_DETAILLED' => 'Estimated time by trainers',
        'LMS_FINISHED_LEARNERS' => 'Learners who have completed',
        'LMS_UNFINISHED_LEARNERS' => 'Learners who have not completed',
        'LMS_ERROR_NOT_A_VALID_MODULE' => 'The module given in parameter does not exist or does not belong to the path',
        'LMS_MISSING_COURSE' => 'There are no "LMS Paths" yet. Please create one, and add "LMS Modules" and "LMS Activities" to it from the page',
        'LMS_MISSING_COURSE_PAGELINK' => 'Database',
        'LMS_DASHBOARD_FINISHED_USER_FILE_SUFFIX' => 'finished-users',
        'LMS_DASHBOARD_UNFINISHED_USER_FILE_SUFFIX' => 'unfinished-users',
        // LearnerDashboard
        'LMS_DASHBOARD' => 'Dashboard: ',
        'LMS_DASHBOARD_TYPE' => 'Type',
        'LMS_DASHBOARD_NAME' => 'Name',
        'LMS_DASHBOARD_PROGRESS' => 'Progress',
        'LMS_DASHBOARD_ELAPSEDTIME' => 'Time spent',
        'LMS_DASHBOARD_FIRSTACCESS' => 'First access',
        'LMS_DASHBOARD_FINISHED_F' => 'Completed',
        'LMS_DASHBOARD_IN_COURSE' => 'In progress',
        'LMS_DASHBOARD_SELECT_USER_TITLE' => 'User\'s choice',
        'LMS_DASHBOARD_EXPORT_TO_CSV' => 'CSV export',
        'LMS_DASHBOARD_LEGEND' => 'Legend',
        'LMS_DASHBOARD_USER_FILE_SUFFIX' => 'dashboard',
        'LMS_DASHBOARD_RETURN' => 'Back to ',
        'LMS_NO_ACTIVITY' => 'No activities',
        'LMS_MODULE_PREVIEW_NOT_POSSIBLE' => 'Not accessible until you start the module ',
        'LMS_ACTIVITY_PREVIEW_NOT_POSSIBLE' => 'Not accessible until you start the module the activity ',
        'LMS_LOGGED_USERS_ONLY_HANDLER' => 'You must be logged in to use the handler',
        'LMS_UPDATE_ELAPSED_TIME_UPDATE' => 'Update',
        'LMS_UPDATE_ELAPSED_TIME_MESSAGE' => 'How much time did you spend on ',
        'LMS_UPDATE_ELAPSED_TIME_MODULE' => 'the module',
        'LMS_UPDATE_ELAPSED_TIME_ACTIVITY' => 'the activity',
        'LMS_UPDATE_ELAPSED_TIME_MINUTES' => 'minutes',

        // action importcourses
        'LMS_IMPORT_TOKEN' => 'Remote site token',
        'LMS_IMPORT_URL' => 'URL to import',
        'LMS_IMPORT' => 'Import',

        // ImportService
        'LMS_ERROR_NO_PEERTUBE_TOKEN' => 'No token for Peertube',
        'LMS_ERROR_NO_CREDENTIALS' => 'No identifiers received for',
        'LMS_FILE' => 'File',
        'LMS_FILE_OVERWRITE' => 'already exists among the files, we overwrite it',
        'LMS_FILE_NO_OVERWRITE' => 'already exists among the files, we do not download it',
        'LMS_ERROR_DOWNLOADING' => 'Error during download',
        'LMS_REMOVING_CORRUPTED_FILE' => 'removing the corrupted file',
        'LMS_ERROR_NO_DATA' => 'no data to analyze',
        'LMS_ERROR_PARSING_DATA' => 'error when decoding JSON format',
        'LMS_ERROR_PROVIDER' => 'Impossible to determine the provider of the video',

        // extra activities
        'LMS_EXTRA_ACTIVITY_ADD' => 'Add an additional activity (workshop, webinar, seminar, ...)',
        'LMS_EXTRA_ACTIVITY_REMOVE' => 'Remove this user',
        'LMS_EXTRA_ACTIVITY_DELETE' => 'Remove the extra activity',
        'LMS_EXTRA_ACTIVITY_EDIT' => 'Modify the additional activity',
        'LMS_EXTRA_ACTIVITIES' => 'Webinars, workshops, seminars, other activities',
        'LMS_EXTRA_ACTIVITY_TITLE' => 'Title of the additional activity',
        'LMS_EXTRA_ACTIVITY_RELATED_LINK' => 'Link or tag page to additional activity details',
        'LMS_EXTRA_ACTIVITY_LINK' => 'Details on the additional activity',
        'LMS_EXTRA_ACTIVITY_BEGIN_DATE' => 'Start date of the additional activity',
        'LMS_EXTRA_ACTIVITY_DATE' => 'Date',
        'LMS_EXTRA_ACTIVITY_END_DATE' => 'End date of the additional activity',
        'LMS_EXTRA_ACTIVITY_ASSOCIATED_COURSE' => 'Associated course',
        'LMS_EXTRA_ACTIVITY_ASSOCIATED_MODULE' => 'Associated module',
        'LMS_EXTRA_ACTIVITY_REGISTERED_LEARNERS' => 'Registered',
        'LMS_EXTRA_ACTIVITY_D' => ' from ',
        'LMS_EXTRA_ACTIVITY_LEARNERS' => 'Learners',
        'LMS_EXTRA_ACTIVITY_ERROR_AT_SAVE' => 'Error when saving additional activity: ',
        'LMS_EXTRA_ACTIVITY_ERROR_AT_DELETE' => 'Error when deleting additional activity: ',
        'LMS_EXTRA_ACTIVITY_ERROR_AT_REMOVE' => 'Error when removing the learner: ',
        'LMS_EXTRA_ACTIVITY_DELETE' => 'Confirm the deletion of the additional activity : ',
        'LMS_EXTRA_ACTIVITY_REMOVE_LEARNER' => 'Confirm the learner removal : ',
        'LMS_EXTRA_ACTIVITY_REMOVE_LEARNER_END' => ', of additional activity : ',
        'LMS_BACK_TO_PROGRESSES_DASHBOARD' => 'Back to the dashboard',
        'LMS_EXTRA_ACTIVITY_SIGNATURE' => 'Signature',
        'LMS_EXTRA_ACTIVITY_CREATE_ATTENDANCE_SHEET' => 'Create a sign-in sheet',

        // QUIZ
        'LMS_QUIZ_RESULTS' => 'Results',
        'LMS_QUIZ_RESULTS_TITLE' => 'Quiz results',
        'LMS_QUIZ_DELETE_ALL' => 'delete all displayed results?',
        'LMS_QUIZ_DELETE' => 'delete the results of this line?',
        'LMS_QUIZ_DELETE_WARNING' => 'Attention! All saved data will be lost. Are you sure you want to ',
        'LMS_QUIZ_FILTER_ON_THIS_USER' => 'Filter results for this user',
        'LMS_QUIZ_FILTER_ON_THIS_QUIZID' => 'Filter results for this quiz',

        // activity navigation conditions
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_ADD' => 'Add a condition',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_REACTION_NEEDED' => 'Reaction requested',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_REACTION_NEEDED_HELP' => 'Please give a reaction',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_REACTION_NEEDED_HELP_ALERT' => 'Please select at least one reaction',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED' => 'Completed quiz',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED_HELP' => 'Please take the quiz',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED_HELP_FOR_ANY' => 'Please take at least one quiz',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_EMPTY' => '(empty = any test of the activity)',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED_WITH_MINIMUM_LEVEL' => 'Quiz passed with a minimum level',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_MINIMUM_LEVEL_HELP' => ' with a minimum score of :',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_LEVEL' => 'Level',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_FORM_FILLED' => 'Completed form',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_FORM_FILLED_HELP' => 'Please add a record to the form',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_FORM' => 'Form',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_ERROR' => 'An error occurred during verification. Please contact a site administrator',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS' => 'Passing conditions',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_NEXT_MODULE_CLOSED' => 'Please wait, the next module is still closed.',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_NEXT_MODULE_TO_BE_OPEN' => 'Please be patient, the next module is not yet open.',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_NEXT_MODULE_NOT_ACCESSIBLE' => 'Please wait, the next module is not accessible.',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_NEXT_ACTIVITY_NOT_ACCESSIBLE' => 'Please wait, the next activity is not available.',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_SCOPE' => 'Scope',
        'LMS_ACTIVITY_NAVIGATION_CONDITIONS_SCOPE_EMPTY' => 'Empty = no constraint',

        // actions/EditConfigAction.php
        'EDIT_CONFIG_HINT_LMS_CONFIG[ACTIVITY_FORM_ID]' => 'LMS Activity form ID',
        'EDIT_CONFIG_HINT_LMS_CONFIG[MODULE_FORM_ID]' => 'LMS Module form ID',
        'EDIT_CONFIG_HINT_LMS_CONFIG[COURSE_FORM_ID]' => 'LMS Course form ID',
        'EDIT_CONFIG_HINT_LMS_CONFIG[ATTENDANCE_SHEET_FORM_ID]' => 'LMS Attendance sheet ID form',
        'EDIT_CONFIG_HINT_LMS_CONFIG[EXTRA_ACTIVITY_ENABLED]' => 'Activate additional activities (webinars, workshops, ...) (true/false)',
        'EDIT_CONFIG_HINT_LMS_CONFIG[SAVE_PROGRESS_FOR_ADMINS]' => 'Save the progress of the administrators (true/false)',
        'EDIT_CONFIG_HINT_LMS_CONFIG[ACTIVITY_NAVIGATION_CONDITIONS_ENABLED]' => 'Activate the conditions of passage for the scripting (true/false)',
        'EDIT_CONFIG_HINT_LMS_CONFIG[DISPLAY_ACTIVITY_TITLE]' => 'Display the title of the activities (true/false)',
        'EDIT_CONFIG_HINT_LMS_CONFIG[MODULE_IMAGE_SIZE_IN_COURSE]' => 'Size of the image of each module on the presentation page of a course (in pixels)',
        'EDIT_CONFIG_HINT_LMS_CONFIG[TABS_ENABLED]' => 'Activate the tabbed activity grouping mode (tab) (true/false)',
        'EDIT_CONFIG_HINT_LMS_CONFIG[USE_ONLY_CUSTOM_ELAPSED_TIME]' => 'Use only custom times in the learner dashboard (true/false)',
        'EDIT_CONFIG_HINT_LMS_CONFIG[DISPLAY_ACTIVITY_ELAPSED_TIME]' => 'Afficher la durée des activités dans le tableau de bord des apprenants (true/false)',
        'EDIT_CONFIG_HINT_LMS_CONFIG[SHOW_ACTIVITIES_WITHOUT_CONTEXT_OR_LEARNER]' => 'Allow access to activities that are not part of a course (true/false)',
        'EDIT_CONFIG_HINT_LMS_CONFIG[LEARNER_FORM_ID]' => 'User profile form identifier used for the learner',
        'EDIT_CONFIG_HINT_LMS_CONFIG[LEARNER_MAIL_FIELD]' => 'Fieldname for the learner\'s email in the user profile form',
        'EDIT_CONFIG_HINT_LMS_CONFIG[PROGRESS_DASHBOARD_FILTERS]' => 'List of learner fields on which to filter the progress dashboard (field names separated by ",")',
    )
);
