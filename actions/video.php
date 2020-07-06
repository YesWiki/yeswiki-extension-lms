<?php
/**
 * Action to display a responsive Vimeo video.
 *
 * @param id    the video id, for vimeo it's a series of figures whereas for youtube it's a series of letters
 * @param serveur  the serveur used, only 'vimeo' and 'youtube' are allowed
 * @param ratio  the ratio to display the video. By defaut, it's a 16/9 ration, if '4par3' is specified a 4/3 ration
 * is applied.
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

!defined('ALLOWED_SERVERS') && define('ALLOWED_SERVERS', array('vimeo', 'youtube'));

$id = $this->GetParameter("id");
$serveur = $this->GetParameter("serveur");

if (empty($id) || empty($serveur) || !in_array(strtolower($serveur), ALLOWED_SERVERS)){
    echo '<div class="alert alert-danger">' . _t('LMS_VIDEO_PARAM_ERROR') . '</div>'."\n";
} else {
    $ratio = $this->GetParameter("ratio");

    if ($ratio == '4par3')
        $radioCss = 'embed-responsive-4by3';
    else
        $ratioCss = 'embed-responsive-16by9';

    if ($serveur == 'vimeo')
        echo '<div class="embed-responsive ' . $ratioCss . '"><iframe src="https://player.vimeo.com/video/' . $id
            . '?color=ffffff&title=0&byline=0&portrait=0" class="embed-responsive-item" frameborder="0"'
            . 'allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen" '
            . 'allowfullscreen></iframe></div>';
    else
        echo '<div class="embed-responsive ' . $ratioCss . '"><iframe src="https://www.youtube-nocookie.com/embed/' . $id
            . '?cc_load_policy=1&iv_load_policy=3&modestbranding=1" class="embed-responsive-item" frameborder="0"'
            . 'allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen" '
            . 'allowfullscreen></iframe></div>';
}
