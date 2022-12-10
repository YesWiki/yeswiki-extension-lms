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
    protected const FIELD_LABEL_REACTION = 6;
    protected const DEFAULT_REACTIONS = [
        'top-gratitude' => [
            'title_t' => 'LMS_REACTIONS_DEFAULT_GRATITUDE',
            'image' => 'tools/lms/presentation/images/mikone-top-gratitude.svg',
        ],
        'j-aime' => [
            'title_t' => 'LMS_REACTIONS_DEFAULT_I_LOVE',
            'image' => 'tools/lms/presentation/images/mikone-j-aime.svg',
        ],
        'j-ai-appris' => [
            'title_t' => 'LMS_REACTIONS_DEFAULT_I_UNDERSTOOD',
            'image' => 'tools/lms/presentation/images/mikone-j-ai-appris.svg',
        ],
        'pas-compris' => [
            'title_t' => 'LMS_REACTIONS_DEFAULT_NOT_UNDERSTOOD',
            'image' => 'tools/lms/presentation/images/mikone-pas-compris.svg',
        ],
        'pas-d-accord' => [
            'title_t' => 'LMS_REACTIONS_DEFAULT_NOT_AGREE',
            'image' => 'tools/lms/presentation/images/mikone-pas-d-accord.svg',
        ],
        'idee-noire' => [
            'title_t' => 'LMS_REACTIONS_DEFAULT_BLACK_IDEA',
            'image' => 'tools/lms/presentation/images/mikone-idee-noire.svg',
        ]
    ];
    public const DEFAULT_OPTIONS = [
        'oui' => 'YES',
        'non' => 'NO'
    ];
    public const DEFAULT_OK_KEY = 'oui';

    protected $ids;
    protected $titles;
    protected $images;
    protected $imagesPath;
    protected $options;
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
        $this->imagesPath = null;
        $this->options = array_map('_t',self::DEFAULT_OPTIONS);

        $this->label = $values[self::FIELD_LABEL_REACTION] ?? '';
        if (empty(trim($this->label))){
            $this->label = _t('LMS_ACTIVATE_REACTIONS');
        }
        // reset not used values
        $this->size = null;
        $this->maxChars = null;

        $this->ids = $values[self::FIELD_IDS];
        $this->ids = explode(',', $this->ids);
        $this->ids = array_map('trim', $this->ids);
        // if empty, we use default values
        if (count($this->ids) == 1 && empty($this->ids[0])) {
            $this->ids = array_keys(self::DEFAULT_REACTIONS);
        }

        $this->titles = $values[self::FIELD_TITLES];
        $this->titles = explode(',', $this->titles);
        $this->titles = array_map('trim', $this->titles);
        foreach ($this->ids as $k => $id) {
            if (empty($this->titles[$k])) {
                // if ids are default ones, we have some titles
                $this->titles[$k] = (array_key_exists($id,self::DEFAULT_REACTIONS))
                    ? _t(self::DEFAULT_REACTIONS[$id]['title_t'])
                    : $id ; // we show just the id, as it's our only information available
            }
        }

        $this->images = $values[self::FIELD_IMAGES];
        $this->images = explode(',', $this->images);
        $this->images = array_map('trim', $this->images);

        // load the lms lib
        require_once LMS_PATH . 'libs/lms.lib.php';
    }

    // Render the show view of the field
    protected function renderStatic($entry)
    {
        // the tag of the current entry
        $currentEntryTag = $this->getCurrentTag($entry);

        if (is_null($currentEntryTag) || $this->getValue($entry) !== self::DEFAULT_OK_KEY) {
            return "" ;
        }

        // get reactions numbers for templating later
        $r = getAllReactions($entry['id_fiche'], $this->ids, $this->wiki->getUsername());

        $reactions = [];

        $imagesPath = $this->getImagesPath();

        foreach ($this->ids as $k => $id) {
            if (!empty($imagesPath[$k])) {
                $nbReactions = $r['reactions'][$id];
            }
            $reactions[$id] = [
                'id' => $id,
                'nbReactions' => $nbReactions ?? null,
                'image' => $imagesPath[$k] ?? null,
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

    // lazy loading
    protected function getImagesPath(): array
    {
        if (is_null($this->imagesPath)){
            $imagesPath = [];
            foreach ($this->ids as $k => $id) {
                if (empty($this->images[$k]) || empty(trim($this->images[$k]))) { // if ids are default ones, we have some images
                    $imagesPath[$k] = (array_key_exists($id,self::DEFAULT_REACTIONS))
                        ? self::DEFAULT_REACTIONS[$id]['image']
                        : '' ;
                } else {
                    $sanitizedImageFilename = basename(trim($this->images[$k]));
                    if (file_exists("files/$sanitizedImageFilename")) { // custom image in files folder
                        $imagesPath[$k] = "files/$sanitizedImageFilename";
                    } elseif (file_exists("tools/lms/presentation/images/mikone-$sanitizedImageFilename.svg")) {
                        $imagesPath[$k] = "tools/lms/presentation/images/mikone-$sanitizedImageFilename.svg";
                    } else {
                        $imagesPath[$k] = '';
                    }
                }
            }
            $this->imagesPath = $imagesPath;
        }
        return $this->imagesPath;
    }

    protected function getCurrentTag($entry): ?string
    {
        // the tag of the current activity page
        return !empty($entry['id_fiche']) ? $entry['id_fiche'] : null;
    }

    protected function renderInput($entry)
    {
        return $this->render('@bazar/inputs/select.twig', [
            'value' => $this->getValue($entry),
            'options' => $this->options
        ]);
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
                'images' => array_map('basename',$this->getImagesPath()),
            ]
        );
    }
}
