<?php
/**
 * Action to display a pdf in an embedded reader
 *
 * @param url  The url of the pdf file. The url has to be from the same origin than the wiki (same schema, same host & same port)
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

// add LMS extension css style
$this->AddCSSFile(LMS_PATH . 'presentation/styles/lms.css');

$url = $this->GetParameter("url");

if (empty($url) || parse_url($url, PHP_URL_HOST) != $_SERVER['SERVER_NAME'] ||
    parse_url($url, PHP_URL_PORT) != $_SERVER['SERVER_PORT'] ||
    parse_url($url, PHP_URL_SCHEME) != $_SERVER['REQUEST_SCHEME']){
    echo '<div class="alert alert-danger">' . _t('LMS_PDF_PARAM_ERROR') . '</div>' . "\n";
} else {
    echo '<div class="embed-responsive pdf"><iframe src="' . LMS_PATH . 'libs/vendor/pdfjs-dist/web/viewer.html?file='
        . urlencode($url) . '" class="embed-responsive-item" frameborder="0"></iframe></div>';
}
