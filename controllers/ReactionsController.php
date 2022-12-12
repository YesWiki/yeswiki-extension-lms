<?php

namespace YesWiki\Lms\Controller;

use URLify;
use YesWiki\Core\Service\ReactionManager;
use YesWiki\Core\YesWikiController;

class ReactionsController extends YesWikiController
{
    protected $reactionManager;

    public function __construct(
        ReactionManager $reactionManager
    ) {
        $this->reactionManager = $reactionManager;
    }

    /**
     * format Reactions Labels
     * @param string $labelsComaSeparated
     * @param null|array $ids
     * @param array $defaultLabels
     * @return array ['labels'=>string[],'ids'=>string[]]]
     */
    public function formatReactionsLabels(string $labelsComaSeparated, ?array $ids = null, array $defaultLabels = []): array
    {
        $rawLabels = array_map('trim', explode(',', $labelsComaSeparated));
        if (is_null($ids)) {
            $labels = $rawLabels;
            $ids = array_map(class_exists(URLify::class, false) ? 'URLify::slug' : [$this,'backupURLify'], $labels);
        } else {
            $labels = [];
            foreach ($ids as $k => $id) {
                $labels[$k] = (!empty($rawLabels[$k]))
                    ? $rawLabels[$k]
                    : (
                        // if ids are default ones, we have some titles
                        (array_key_exists($id, $defaultLabels))
                        ? $defaultLabels[$id]
                        : $id  // we show just the id, as it's our only information available
                    );
            }
        }
        return compact(['labels','ids']);
    }

    /**
     * format Reactions Labels
     * @param array $ids
     * @param string $imagesComaSeparated
     * @param array $defaultImages
     * @return string[]
     */
    public function formatImages(array $ids, string $imagesComaSeparated, array $defaultImages = []): array
    {
        $rawImages = array_map('trim', explode(',', $imagesComaSeparated));
        $images = [];
        foreach ($ids as $k => $id) {
            $sanitizedImageFilename = empty($rawImages[$k]) ? '' : basename($rawImages[$k]);
            $images[$k] = empty($rawImages[$k]) // if ids are default ones, we have some images
                ? (
                    (array_key_exists($k, $defaultImages))
                    ? $defaultImages[$k]
                    : ''
                )
                : (
                    basename($rawImages[$k]) !== $rawImages[$k]
                    ? '' // error
                    : (
                        (preg_match('/\\.(gif|jpeg|png|jpg|svg|webp)$/i', $rawImages[$k]))
                        ? (
                            file_exists("custom/images/{$rawImages[$k]}")
                            ? "custom/images/{$rawImages[$k]}"
                            : (
                                file_exists("files/{$rawImages[$k]}")
                                ? "files/{$rawImages[$k]}"
                                : '' // error
                            )
                        )
                        : (
                            file_exists("tools/lms/presentation/images/mikone-{$rawImages[$k]}.svg")
                            ? "tools/lms/presentation/images/mikone-{$rawImages[$k]}.svg"
                            : $rawImages[$k]
                        )
                    )
                );
        }
        return $images;
    }

    private function backupURLify(string $text): string
    {
        return strreplace(["\n","\r","\t","'"," ",'.'], '-', strtolower($text));
    }

    /**
     * @param string $pageTag
     * @param string $userName
     * @param string $reactionId
     * @param array $ids
     * @param array $labels
     * @param array $images
     * @param bool $isDefaultReactionFied = false
     * @return array [
     *  'reactions' => [
     *      (string $id) => [
     *          'id'=>string,
     *          'label'=>string,
     *          'image'=>string,
     *          'nbReactions'=>integer
     *      ]
     *  ],
     *  'userReactions' = >string[] $ids
     * ]
     */
    public function getReactionItems(string $pageTag, string $userName, string $reactionId, array $ids, array $labels, array $images, bool $isDefaultReactionFied = false): array
    {
        $reactions = [];
        $userReactions = [];
        $uniqueIds = ["$reactionId|$pageTag"];
        if ($isDefaultReactionFied) {
            $uniqueIds[] = "reactionField|$pageTag";
        }
        foreach ($ids as $k => $id) {
            $reactions[$id] = [
                'id' => $id,
                'label' => $labels[$k] ?? '',
                'image' => $images[$k] ?? '',
                'nbReactions'=>0
            ];
        }
        $allReactions = $this->reactionManager->getReactions($pageTag, [$reactionId]);
        foreach ($uniqueIds as $uniqueId) {
            if (!empty($allReactions[$uniqueId]['reactions'])) {
                foreach ($allReactions[$uniqueId]['reactions'] as $reaction) {
                    if (isset($reactions[$reaction['id']])) {
                        $reactions[$reaction['id']]['nbReactions'] = $reactions[$reaction['id']]['nbReactions'] + 1;
                        if (!empty($userName) && $reaction['user'] === $userName && !in_array($reaction['id'], $userReactions)) {
                            $userReactions[] = $reaction['id'];
                        }
                    }
                }
            }
        }
        return compact(['reactions','userReactions']);
    }
}
