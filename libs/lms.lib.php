<?php
/**
 * Library of the LMS users functions
 *
 * @category YesWiki
 * @package  lms
 * @author   Adrien Cheype <adrien.cheype@gmail.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */
use YesWiki\Bazar\Service\EntryManager;

function getAllReactions($pageTag, $ids, $user){
    $res = ['reactions' => [], 'userReaction' => ''];
    // initialise empty reactions
    foreach ($ids as $id) {
          $res['reactions'][$id]= 0;
    }
    // get reactions in db
    $val = $GLOBALS['wiki']->getAllTriplesValues($pageTag, 'https://yeswiki.net/vocabulary/reaction', '', '');
    foreach ($val as $v) {
        $v = json_decode($v['value'], true);
        if (count($v)>0) {
            if ($v['user'] == $user ) {
                $res['userReaction'] = $v['id'];
            }
            // check for existance of reaction
            if (isset($res['reactions'][$v['id']])) {
                $res['reactions'][$v['id']]++;
            }
        }
    }
    return $res;
}

function getUserReactionOnPage($pageTag, $user){
    $res = '';
    // get reactions in db
    $val = $GLOBALS['wiki']->getAllTriplesValues($pageTag, 'https://yeswiki.net/vocabulary/reaction', '', '');
    foreach ($val as $v) {
        $v = json_decode($v['value'], true);
        if (!empty($v)) {
            $res = $v;
        }
    }
    return $res;
}
