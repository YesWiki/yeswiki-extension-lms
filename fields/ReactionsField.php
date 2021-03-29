<?php

namespace YesWiki\Lms\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Wiki;

class ReactionsField extends LmsField
{
    protected const FIELD_IDS = 2;
    protected const FIELD_TITLES = 3;
    protected const FIELD_IMAGES = 4;
    protected const DEFAULT_REACTIONS = ['top-gratitude', 'j-aime', 'j-ai-appris', 'pas-compris', 'pas-d-accord', 'idee-noire'];

    protected $ids;
    protected $titles;
    protected $images;

    /*
     * Display the possible reactions to comment an activity.
     * Must be declare in the bazar form definition as followed :
     *    'reactions***idreaction1,idreaction2,idreaction3***titlereaction1,titlereaction2,titlereaction3***image1,image2,image3*** *** *** *** *** *** ***'
     * Some ids are generic and have associated images and titles : j-ai-appris,j-aime,pas-clair,pas-compris,pas-d-accord,top-gratitude
     * otherwise, you will need to give a filename that is included in files directory
     */
    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);

        $this->wiki = $services->get(Wiki::class);

        // reset not used values
        $this->label = null;
        $this->size = null;
        $this->maxChars = null;
        
        $this->ids = $values[self::FIELD_IDS];
        $this->ids = explode(',', $this->ids);
        $this->ids = array_map('trim', $this->ids);
        // if empty, we use default values
        if (count($this->ids) == 1 && empty($this->ids[0])) {
            $this->ids = self::DEFAULT_REACTIONS;
        }

        $this->titles = $values[self::FIELD_TITLES];
        $this->titles = explode(',', $this->titles);
        $this->titles = array_map('trim', $this->titles);

        $this->images = $values[self::FIELD_IMAGES];
        $this->images = explode(',', $this->images);
        $this->images = array_map('trim', $this->images);
        // TODO : check realpath for security
        // $images = array_map('realpath', $images);

        // load the lms lib
        require_once LMS_PATH . 'libs/lms.lib.php';
    }

    protected function getAllReactions($pageTag, $ids, $user)
    {
        return getAllReactions($pageTag, $ids, $user);
    }

    // Render the show view of the field
    protected function renderStatic($entry)
    {
        // the tag of the current entry
        $currentEntryTag = $this->getCurrentTag($entry);

        if (is_null($currentEntryTag) || empty($entry['listeListeOuinonLmsbf_reactions']) || $entry['listeListeOuinonLmsbf_reactions'] != "oui") {
            return null ;
        }
        $outputreactions = '';
        // get reactions numbers for templating later
        $r = $this->getAllReactions($entry['id_fiche'], $this->ids, $this->wiki->getUsername());

        foreach ($this->ids as $k => $id) {
            if (empty($htis->titles[$k])) { // if ids are default ones, we have some titles
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
                $title = $this->titles[$k]; // custom title
            }
            if (empty($this->images[$k])) { // if ids are default ones, we have some images
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
                if (file_exists('files/' . $this->images[$k])) { // custom image in files folder
                    $image = 'files/' . $this->images[$k];
                } elseif (file_exists(LMS_PATH . 'presentation/images/mikone-' . $this->images[$k] . '.svg')) {
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
            if ($this->wiki->getUser()) {
                $extraClass = (!empty($r['userReaction']) && $id == $r['userReaction']) ? ' user-reaction' : '';
                $params = ['id' => $id] + (!empty($_GET['course']) && $_GET['course'] ? ['course' => $_GET['course']] : [])
                    + (!empty($_GET['module']) && $_GET['module'] ? ['module' => $_GET['module']] : []);
                $outputreactions .= '<a href="' . $this->wiki->href(
                    'reaction',
                    '',
                    $params
                ) . '" class="add-reaction' . (!empty($extraClass) ? '' . $extraClass : '') . '">' . $reaction . '</a>';
            } else {
                $outputreactions .= '<a href="#" onclick="return false;" title="' . _t('LMS_LOGIN_TO_REACT') . '" class="disabled add-reaction">' . $reaction . '</a>';
            }
            $outputreactions .= '</div>';
        }
        if ($this->wiki->getUser()) {
            $msg = _t('LMS_SHARE_YOUR_REACTION');
        } else {
            $msg = _t('LMS_TO_ALLOW_REACTION') . ', <a href="#LoginModal" class="btn btn-primary" data-toggle="modal">' . _t('LMS_PLEASE_LOGIN') . '</a>';
        }
        $output = '<hr /><div class="reactions-container"><h5>' . $msg . '</h5><div class="reactions-flex">' . $outputreactions . '</div>';
        if ($this->wiki->getUser()) {
            $output .= '<em>' . _t('LMS_SHARE_YOUR_COMMENT') . '</em>';
        }
        $output .= '</div>' . "\n";
        return $output;
    }
}
