<?php

namespace YesWiki\Lms;

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Lms\Service\DateManager;

abstract class CourseStructure
{
    // the object tag
    protected $tag;

    // the next fields are lazy loaded : don't use direct access to them, call the getters instead
    protected $fields; // entry fields of the object
    protected $extraActivityLogs; // extraActivityLogs of the CourseStructure

    // the configuration parameters of YesWiki
    protected $config;
    // manager used to get object entries
    protected $entryManager;
    // manager used to format dates
    protected $dateManager;
    // list of learners who have opened
    protected $learnersWhoHaveOpened ;
    // list of learners who can open
    protected $learnersWhoCanOpen ;
    
    /**
     * CourseStructure constructor
     * @param array $config the configuration parameters of YesWiki
     * @param EntryManager $entryManager the manager used to get object entries
     * @param DateManager $dateManager the manager used to format dates
     * @param string $objectTag the object tag
     * @param array|null $objectFields the object fields if needed to populate directly the object
     */
    public function __construct(
        array $config,
        EntryManager $entryManager,
        DateManager $dateManager,
        string $objectTag,
        array $objectFields = null
    ) {
        $this->tag = $objectTag;

        if ($objectFields !== null) {
            $this->fields = $objectFields;
        }

        $this->config = $config;
        $this->entryManager = $entryManager;
        $this->dateManager = $dateManager;
        $this->extraActivityLogs = new ExtraActivityLogs() ;
        $this->learnersWhoHaveOpened = [];
        $this->learnersWhoCanOpen = [];
    }

    /**
     * Get the object tag
     * @return string the object tag
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * Get the entry fields of the object
     * @return array|null the object fields or null if no entry associated to the object tag
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
     * Get a specific field of the object
     * This is shortcut for ->getFields()[key].
     *
     * @param string $key the key field
     * @return mixed|null the field value or null if not defined
     */
    public function getField(string $key)
    {
        return key_exists($key, $this->getFields()) ? $this->getFields()[$key] : null;
    }

    /**
     * Get the object title
     * @return string|null the object title or null if not defined
     */
    public function getTitle(): ?string
    {
        return $this->getField('bf_titre');
    }

    
    /**
     * Get the Extra-activities of the courseStructure
     *
     * @return ExtraActivityLogs the courseStructure's extraActivityLogs
     */
    public function getExtraActivityLogs(): ExtraActivityLogs
    {
        return $this->extraActivityLogs;
    }

    
    /**
     * Set the Extra-activities of the courseStructure
     *
     * @param ExtraActivityLogs the courseStructure's extraActivityLogs
     */
    public function setExtraActivityLogs(ExtraActivityLogs $extraActivityLogs)
    {
        $this->extraActivityLogs = $extraActivityLogs;
    }
    
    /**
     * check if a learner has opened this CourseStructure
     * @param Learner $learner
     * @param bool|null $newValue
     * @return bool|null
     */
    public function hasBeenOpenedBy(Learner $learner, ?bool $newValue = null): ?bool
    {
        if (!is_null($newValue)) {
            $this->learnersWhoHaveOpened[$learner->getUserName()] = $newValue;
        }
        return $this->learnersWhoHaveOpened[$learner->getUserName()] ?? null ;
    }

    /**
     * check if a learner can open this CourseStructure
     * @param Learner $learner
     * @param bool|null $newValue
     * @return bool|null
     */
    public function canBeOpenedBy(Learner $learner, ?bool $newValue = null): ?bool
    {
        if (!is_null($newValue)) {
            $this->learnersWhoCanOpen[$learner->getUserName()] = $newValue;
        }
        return $this->learnersWhoCanOpen[$learner->getUserName()] ?? null ;
    }
}
