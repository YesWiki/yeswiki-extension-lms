<?php

namespace YesWiki\Lms\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Bazar\Field\BazarField;
use YesWiki\Lms\Controller\CourseController;
use YesWiki\Lms\Service\LearnerManager;

abstract class LmsField extends BazarField
{
    /**
     * Display the 'Précédent', 'Suivant' and 'Fait !' buttons which permits to a learner to navigate in an activity or module page
     */

    protected $courseController;
    protected $learnerManager;
    protected $moduleModal;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);

        $this->courseController = $services->get(CourseController::class);
        $this->learnerManager = $services->get(LearnerManager::class);
        $this->moduleModal = null;
    }

    protected function getCurrentTag($entry): ?string
    {
        // the tag of the current activity page
        return !empty($entry['id_fiche']) ? $entry['id_fiche'] : null;
    }

    protected function renderInput($entry)
    {
        // No input need to be displayed for this example field
        return null;
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

    public function getModuleModal()
    {
        return $this->moduleModal;
    }

    
    public function jsonSerialize()
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'moduleModal' => $this->getModuleModal()
            ]
        );
    }
}
