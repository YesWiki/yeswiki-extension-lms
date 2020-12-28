<?php

namespace YesWiki\Lms;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Wiki;

class Activity
{
    // the activity tag
    protected $tag;

    // the next fiels are lazy loaded : don't use direct access to them, call the getters instead
    protected $fields; // entry fields of the activity

    // manager used to get activity entries
    protected $entryManager;

    /**
     * Activity constructor
     *
     * @param EntryManager $entryManager the manager used to get activity entries
     * @param string $activityTag the activity tag
     * @param array|null $activityFields the activity fields if needed to populate directly the object
     */
    public function __construct(EntryManager $entryManager, string $activityTag, array $activityFields = null)
    {
        $this->tag = $activityTag;

        if ($activityFields !== null) {
            $this->fields = $activityFields;
        }

        $this->entryManager = $entryManager;
    }

    /**
     * Get the activity tag
     * @return string the module tag
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * get the entry fields of the activity
     *
     * @return array|null the activity fields
     */
    public function getFields(): ?array
    {
        // lazy loading
        if (is_null($this->fields)) {
            $this->fields = $this->entryManager->getOne($this->getTag());
        }
        return $this->fields;
    }

    /**
     * get a specific field of the activity
     * this is shortcut for ->getFields()[key]
     *
     * @return mixed the field
     */
    public function getField(string $key)
    {
        return key_exists($key, $this->getFields()) ? $this->getFields()[$key] : null;
    }
}
