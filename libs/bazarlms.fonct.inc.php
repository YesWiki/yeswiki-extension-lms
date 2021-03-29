<?php
/**
 * Extension of bazar for the LMS module
 *
 * @category YesWiki
 * @package  lms
 * @author   Adrien Cheype <adrien.cheype@gmail.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.en.html AGPL 3.0
 * @link     https://yeswiki.net
 */

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Controller\CourseController;
use YesWiki\Lms\Course;
use YesWiki\Lms\ModuleStatus;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\DateManager;
use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Wiki;

/**
 * Display the possible reactions to comment an activity.
 * Must be declare in the bazar form definition as followed :
 *    'reactions***idreaction1,idreaction2,idreaction3***titlereaction1,titlereaction2,titlereaction3***image1,image2,image3*** *** *** *** *** *** ***'
 * Some ids are generic and have associated images and titles : j-ai-appris,j-aime,pas-clair,pas-compris,pas-d-accord,top-gratitude
 * otherwise, you will need to give a filename that is included in files directory
 *
 * cf. formulaire.fonct.inc.php of the bazar extension to see the other field definitions
 *
 * @param array $formtemplate
 * @param array $tableau_template The bazar field definition inside the form definition
 * @param string $mode Action type for the form : 'saisie', 'requete', 'html', ...
 * @param array $fiche The entry which is displayed or modified
 * @return string Return the generated html to include
 */
function reactions(&$formtemplate, $tableau_template, $mode, $fiche)
{

    // the tag of the current entry
    $currentEntryTag = !empty($fiche['id_fiche']) ? $fiche['id_fiche'] : '';

    // TODO refactor it by using the model and the twig template
    if ($mode == 'html' && $currentEntryTag && !empty($fiche['listeListeOuinonLmsbf_reactions']) && $fiche['listeListeOuinonLmsbf_reactions'] == "oui") {
        // load the lms lib
        require_once LMS_PATH . 'libs/lms.lib.php';

        $ids = explode(',', $tableau_template[2]);
        $ids = array_map('trim', $ids);
        // if empty, we use default values
        if (count($ids) == 1 && empty($ids[0])) {
            $ids = ['top-gratitude', 'j-aime', 'j-ai-appris', 'pas-compris', 'pas-d-accord', 'idee-noire'];
        }
        $titles = explode(',', $tableau_template[3]);
        $titles = array_map('trim', $titles);
        $images = explode(',', $tableau_template[4]);
        $images = array_map('trim', $images);
        // TODO : check realpath for security
        // $images = array_map('realpath', $images);
        $outputreactions = '';
        // get reactions numbers for templating later
        $r = getAllReactions($fiche['id_fiche'], $ids, $GLOBALS['wiki']->getUsername());

        foreach ($ids as $k => $id) {
            if (empty($titles[$k])) { // if ids are default ones, we have some titles
                switch ($id) {
                    case 'j-ai-appris':
                        $title = "J'ai appris quelque chose";
                        break;
                    case 'j-aime':
                        $title = "J'aime";
                        break;
                    case 'idee-noire':
                        $title = "Ca me perturbe";
                        break;
                    case 'pas-compris':
                        $title = "J'ai pas compris";
                        break;
                    case 'pas-d-accord':
                        $title = "Je ne suis pas d'accord";
                        break;
                    case 'top-gratitude':
                        $title = "Gratitude";
                        break;
                    default:
                        $title = $id;  // we show just the id, as it's our only information available
                        break;
                }
            } else {
                $title = $titles[$k]; // custom title
            }
            if (empty($images[$k])) { // if ids are default ones, we have some images
                switch ($id) {
                    case 'j-ai-appris':
                    case 'j-aime':
                    case 'idee-noire':
                    case 'pas-compris':
                    case 'pas-d-accord':
                    case 'top-gratitude':
                        $image = LMS_PATH . 'presentation/images/mikone-' . $id . '.svg';
                        break;
                    default:
                        $image = false;
                        break;
                }
            } else {
                if (file_exists('files/' . $images[$k])) { // custom image in files folder
                    $image = 'files/' . $images[$k];
                } elseif (file_exists(LMS_PATH . 'presentation/images/mikone-' . $images[$k] . '.svg')) {
                    $image = LMS_PATH . 'presentation/images/mikone-' . $id . '.svg';
                } else {
                    $image = false;
                }
            }
            if (!$image) {
                $reaction = '<div class="alert alert-danger">Image non trouvée...</div>';
            } else {
                $nbReactions = $r['reactions'][$id];
                $reaction = '<img class="reaction-img" alt="icon ' . $id . '" src="' . $image . '" />
                    <h6 class="reaction-title">' . $title . '</h6>
                    <div class="reaction-numbers">' . $nbReactions . '</div>';
            }
            $outputreactions .= '<div class="reaction-content">';
            if ($GLOBALS['wiki']->getUser()) {
                $extraClass = (!empty($r['userReaction']) && $id == $r['userReaction']) ? ' user-reaction' : '';
                $params = ['id' => $id] + (!empty($_GET['course']) && $_GET['course'] ? ['course' => $_GET['course']] : [])
                    + (!empty($_GET['module']) && $_GET['module'] ? ['module' => $_GET['module']] : []);
                $outputreactions .= '<a href="' . $GLOBALS['wiki']->href(
                    'reaction',
                    '',
                    $params
                ) . '" class="add-reaction' . (!empty($extraClass) ? '' . $extraClass : '') . '">' . $reaction . '</a>';
            } else {
                $outputreactions .= '<a href="#" onclick="return false;" title="' . _t('LMS_LOGIN_TO_REACT') . '" class="disabled add-reaction">' . $reaction . '</a>';
            }
            $outputreactions .= '</div>';
        }
        if ($GLOBALS['wiki']->getUser()) {
            $msg = _t('LMS_SHARE_YOUR_REACTION');
        } else {
            $msg = _t('LMS_TO_ALLOW_REACTION') . ', <a href="#LoginModal" class="btn btn-primary" data-toggle="modal">' . _t('LMS_PLEASE_LOGIN') . '</a>';
        }
        $output = '<hr /><div class="reactions-container"><h5>' . $msg . '</h5><div class="reactions-flex">' . $outputreactions . '</div>';
        if ($GLOBALS['wiki']->getUser()) {
            $output .= '<em>' . _t('LMS_SHARE_YOUR_COMMENT') . '</em>';
        }
        $output .= '</div>' . "\n";
        return $output;
    }
}
