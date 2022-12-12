<?php

namespace YesWiki\Lms\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Bazar\Field\BazarField;
use YesWiki\Core\Controller\AuthController;
use YesWiki\Core\Service\ReactionManager;
use YesWiki\Lms\Controller\ReactionsController;
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

    public const DEFAULT_REACTIONS = [
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
    protected $reactionsController;
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

        $this->reactionsController = $services->get(ReactionsController::class);
        $this->wiki = $services->get(Wiki::class);
        $this->imagesPath = null;
        $this->options = array_map('_t', self::DEFAULT_OPTIONS);

        $this->label = $values[self::FIELD_LABEL_REACTION] ?? '';
        if (empty(trim($this->label))) {
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

        list('labels'=>$this->titles) = $this->reactionsController->formatReactionsLabels(
            isset($values[self::FIELD_TITLES]) && is_string($values[self::FIELD_TITLES])
                ? $values[self::FIELD_TITLES]
                : '',
            $this->ids,
            array_map(function ($reactionData) {
                return _t($reactionData['title_t']);
            }, self::DEFAULT_REACTIONS)
        );

        $this->images = isset($values[self::FIELD_IMAGES]) && is_string($values[self::FIELD_IMAGES]) ? $values[self::FIELD_IMAGES] : '';
    }

    // Render the show view of the field
    protected function renderStatic($entry)
    {
        // the tag of the current entry
        $currentEntryTag = $this->getCurrentTag($entry);

        if (is_null($currentEntryTag) || $this->getValue($entry) !== self::DEFAULT_OK_KEY) {
            return "" ;
        }

        $user = $this->getService(AuthController::class)->getLoggedUser();
        $username = empty($user['name']) ? '' : $user['name'];

        $imagesPath = $this->getImagesPath();
        list('reactions'=>$reactionItems, 'userReactions'=>$userReactions, 'oldIdsUserReactions'=>$oldIdsUserReactions) =
            $this->reactionsController->getReactionItems(
                $currentEntryTag,
                $username,
                $this->name,
                $this->ids,
                $this->titles,
                $this->getImagesPath(),
                true
            );

        return $this->render("@lms/fields/reactions.twig", [
            'reactionId' => $this->name,
            'reactionItems' => $reactionItems,
            'userName' => $username,
            'userReaction' => $userReactions,
            'oldIdsUserReactions' => $oldIdsUserReactions,
            'maxReaction' => 1,
            'pageTag' => $currentEntryTag
        ]);
    }

    // lazy loading
    protected function getImagesPath(): array
    {
        if (is_null($this->imagesPath)) {
            $this->imagesPath = $this->reactionsController->formatImages(
                $this->ids,
                $this->images,
                array_map(function ($reactionsData) {
                    return $reactionsData['image'];
                }, self::DEFAULT_REACTIONS)
            );
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
                'images' => array_map('basename', $this->getImagesPath()),
            ]
        );
    }
}
