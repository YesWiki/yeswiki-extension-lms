<?php
/**
 * Action called after the 'footer' action. Load in the footer the lms depedencies (js, css).
 * These librairies can also be loaded in the wiki.php of the extension but in this case, these dependencies will not appear
 * in the result of $GLOBALS['wiki']->Footer().
 *
 * @category YesWiki
 * @category JdN
 * @author   Adrien Cheype <adrien.cheype@gmail.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */
if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

// add LMS extension css style TODO: fix custom path
$this->AddCSSFile('tools/lms/presentation/styles/lms.css');
// add LMS extension js TODO: fix custom path
$this->AddJavascriptFile('tools/lms/libs/lms.js');
?>
