<?php

namespace YesWiki\Lms;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Service\EntryManager;

abstract class LmsObject
{
    // the object tag
    protected $tag;

    // the next fields are lazy loaded : don't use direct access to them, call the getters instead
    protected $fields; // entry fields of the object

    // the configuration parameters of YesWiki
    protected $config;
    // manager used to get object entries
    protected $entryManager;

    /**
     * object constructor
     * @param ParameterBagInterface $config the configuration parameters of YesWiki
     * @param EntryManager $entryManager the manager used to get object entries
     * @param string $objectTag the object tag
     * @param array|null $objectFields the object fields if needed to populate directly the object
     */
    public function __construct(ParameterBagInterface $config,EntryManager $entryManager, string $objectTag, array $objectFields = null)
    {
        $this->tag = $objectTag;

        if ($objectFields !== null) {
            $this->fields = $objectFields;
        }
        
        $this->config = $config;
        $this->entryManager = $entryManager;
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
     * get the entry fields of the object
     *
     * @return array|null the object fields
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
     * get a specific field of the object
     * this is shortcut for ->getFields()[key]
     *
     * @return mixed the field
     */
    public function getField(string $key)
    {
        return key_exists($key, $this->getFields()) ? $this->getFields()[$key] : null;
    }
}
