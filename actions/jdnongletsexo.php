<?php
/**
 * Specific action for Les Jardiniers du Nous
 * Write a tab with two links, first the exercise then its results
 * TODO Make more generic
 *
 * @category YesWiki
 * @category JdN
 * @author   Adrien Cheype <adrien.cheype@gmail.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

!defined('RESULT_SUFFIX') && define('RESULT_SUFFIX', '2');
!defined('ADD_ENTRY_TEXT') && define('ADD_ENTRY_TEXT', 'Ajouter une fiche');
!defined('SEE_RESULTS_TEXT') && define('SEE_RESULTS_TEXT', 'Voir les résultats');

if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

if (!endsWith($this->GetPageTag(), RESULT_SUFFIX)){
    $exercise = $this->GetPageTag();
    $results = $this->GetPageTag() . RESULT_SUFFIX;
} else {
    $exercise = preg_replace('/' . RESULT_SUFFIX . '$/', '', $this->GetPageTag());
    $results = $this->GetPageTag();
}

$nav = '{{nav id="ongletsexo" links="' . $exercise . ',' . $results . '" titles="' . ADD_ENTRY_TEXT . ',' . SEE_RESULTS_TEXT .
    '" icons="edit,bars" class="nav nav-tabs"}}';
echo $this->Format($nav);
