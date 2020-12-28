<?php

namespace YesWiki\Lms\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiService;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Module;

class CourseManager
{
    protected $config;
    protected $entryManager;

    /**
     * CourseManager constructor
     * @param ParameterBagInterface $config the injected configuration instance
     * @param EntryManager $entryManager the injected EntryManager instance
     */
    public function __construct(ParameterBagInterface $config, EntryManager $entryManager)
    {
        $this->config = $config;
        $this->entryManager = $entryManager;
    }

    /**
     * Load an Activity from its entry tag
     * @param string $entryTag the entry tag corresponding to the activity
     * @param array|null $activityFields the activity fields if needed to populate directly the object
     * @return Activity|null the activity or null if the entry is not an activity
     */
    public function getActivity(string $entryTag, array $activityFields = null): ?Activity
    {
        $activityEntry = $this->entryManager->getOne($entryTag);
        if ($activityEntry && intval($activityEntry['id_typeannonce']) == $this->config->get('lms_config')['activity_form_id']) {
            return new Course($this->config, $this->entryManager, $activityEntry['id_fiche'], $activityEntry);
        } else {
            return null;
        }
    }

    /**
     * Load a Module from its entry tag
     * @param string $entryTag the entry tag corresponding to the module
     * @param array|null $moduleFields the module fields if needed to populate directly the object
     * @return Module|null the module or null if the entry is not a module
     */
    public function getModule(string $entryTag, array $moduleFields = null): ?Module
    {
        $moduleEntry = $this->entryManager->getOne($entryTag);
        if ($moduleEntry && intval($moduleEntry['id_typeannonce']) == $this->config->get('lms_config')['module_form_id']) {
            return new Module($this->config, $this->entryManager, $moduleEntry['id_fiche'], $moduleEntry);
        } else {
            return null;
        }
    }

    /**
     * Load a Course from its entry tag
     * @param string $entryTag the entry tag corresponding to the course
     * @param array|null $courseFields the course fields if needed to populate directly the object
     * @return Module|null the course or null if the entry is not a course
     */
    public function getCourse(string $entryTag, array $courseFields = null): ?Course
    {
        $courseEntry = $this->entryManager->getOne($entryTag);
        if ($courseEntry && intval($courseEntry['id_typeannonce']) == $this->config->get('lms_config')['course_form_id']) {
            return new Course($this->config, $this->entryManager, $courseEntry['id_fiche'], $courseEntry);
        } else {
            return null;
        }
    }

    /**
     * Get all existing Course
     * @return Course[] the list of Course
     */
    public function getAllCourses(): array
    {
        $entries = $this->entryManager->search(['formsIds' => [$this->config->get('lms_config')['course_form_id']]]);

        return empty($entries) ?
            [] :
            array_map(
                function ($courseEntry) {
                    return new Course($this->config, $this->entryManager, $courseEntry['id_fiche'], $courseEntry);
                },
                $entries
            );
    }
}