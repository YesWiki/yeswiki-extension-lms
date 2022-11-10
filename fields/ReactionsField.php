<?php

namespace YesWiki\Lms\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Bazar\Field\BazarField;
use YesWiki\Wiki;

/**
 * @Field({"reactions"})
 */
class ReactionsField extends BazarField
{
    protected const FIELD_IDS = 2;
    protected const FIELD_TITLES = 3;
    protected const FIELD_IMAGES = 4;
    protected const DEFAULT_REACTIONS = ['top-gratitude', 'j-aime', 'j-ai-appris', 'pas-compris', 'pas-d-accord', 'idee-noire'];

    protected $ids;
    protected $titles;
    protected $images;

    protected $linkedFieldName ;
    protected $wiki;

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

        $this->linkedFieldName = 'listeListeOuinonLmsbf_reactions';

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
        foreach ($this->ids as $k => $id) {
            if (empty($this->titles[$k])) { // if ids are default ones, we have some titles
                switch ($id) {
                    case 'j-ai-appris':
                        $this->titles[$k] = "J'ai appris quelque chose";
                        break;
                    case 'j-aime':
                        $this->titles[$k] = "J'aime";
                        break;
                    case 'idee-noire':
                        $this->titles[$k] = "Ca me perturbe";
                        break;
                    case 'pas-compris':
                        $this->titles[$k] = "J'ai pas compris";
                        break;
                    case 'pas-d-accord':
                        $this->titles[$k] = "Je ne suis pas d'accord";
                        break;
                    case 'top-gratitude':
                        $this->titles[$k] = "Gratitude";
                        break;
                    default:
                        $this->titles[$k] = $id;  // we show just the id, as it's our only information available
                        break;
                }
            }
        }

        $this->images = $values[self::FIELD_IMAGES];
        $this->images = explode(',', $this->images);
        $this->images = array_map('trim', $this->images);
        // TODO : check realpath for security
        // $images = array_map('realpath', $images);

        // load the lms lib
        require_once LMS_PATH . 'libs/lms.lib.php';
    }

    // Render the show view of the field
    protected function renderStatic($entry)
    {
        // the tag of the current entry
        $currentEntryTag = $this->getCurrentTag($entry);

        if (is_null($currentEntryTag) || empty($entry[$this->linkedFieldName]) || $entry[$this->linkedFieldName] != "oui") {
            return "" ;
        }

        // get reactions numbers for templating later
        $r = getAllReactions($entry['id_fiche'], $this->ids, $this->wiki->getUsername());

        $reactions = [];

        foreach ($this->ids as $k => $id) {
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
            if ($image) {
                $nbReactions = $r['reactions'][$id];
            }
            $reactions[$id] = [
                'id' => $id,
                'nbReactions' => $nbReactions ?? null,
                'image' => $image ?? null,
                'title' => $this->titles[$k] ?? null,
            ];
        }
        return $this->render("@lms/fields/reactions.twig", [
                'connected' => ($this->wiki->getUser()),
                'course' => $_GET['course'] ?? null,
                'module' => $_GET['module'] ?? null,
                'reactions' => $reactions,
                'userReaction' => $r['userReaction'] ?? null,
            ]);
    }

    protected function getCurrentTag($entry): ?string
    {
        // the tag of the current activity page
        return !empty($entry['id_fiche']) ? $entry['id_fiche'] : null;
    }

    protected function renderInput($entry)
    {
        // No input need to be displayed for this example field
        return "";
    }

    // Format input values before save
    public function formatValuesBeforeSave($entry)
    {
        return [] ;
    }

    protected function getValue($entry)
    {
        return null;
    }

    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'ids' => $this->ids,
                'titles' => $this->titles,
                // 'images' => $this->images, because containing file system path
            ]
        );
    }
}
