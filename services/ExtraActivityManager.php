<?php


namespace YesWiki\Lms\Service;

use YesWiki\Core\Service\TripleStore;
use YesWiki\Wiki;
use YesWiki\Lms\ExtraActivityLog ;
use YesWiki\Lms\ExtraActivityLogs ;
use YesWiki\Lms\Module ;
use YesWiki\Lms\Course ;
use YesWiki\Lms\Controller\ExtraActivityController ;
use YesWiki\Lms\Service\CourseManager;

class ExtraActivityManager
{
    protected const LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY =  'https://yeswiki.net/vocabulary/lms-extra-activity' ;

    protected $tripleStore;
    protected $extraActivityController;
    protected $wiki;
    protected $courseManager ;

    /**
     * LearnerManager constructor
     *
     * @param TripleStore $tripleStore the injected TripleStore instance
     * @param ExtraActivityController $extraActivityController the injected ExtraActivityController instance
     * @param CourseManager $courseManager the injected CourseManager instance
     * @param Wiki $wiki
     */
    public function __construct(
        TripleStore $tripleStore,
        ExtraActivityController $extraActivityController,
        CourseManager $courseManager,
        Wiki $wiki
    ) {
        $this->tripleStore = $tripleStore;
        $this->extraActivityController = $extraActivityController;
        $this->courseManager = $courseManager;
        $this->wiki = $wiki;
    }

    /**
     * Save a Extra-activity
     *
     * @return bool
     */
    public function saveExtraActivity(array $data): bool
    {
        return false;
    }

    /**
     * Get the Extra-activities of a courseStructure
     *
     * @return ExtraActivityLogs the courseStructure's extraActivities
     */
    public function getExtraActivities(Course $course, Module $module = null): ExtraActivityLogs
    {
        if (!$this->extraActivityController->getTestMode()) {
            return new ExtraActivityLogs();
        }

        $like = '%"course":"' . $course->getTag() . '"%';
        if (!is_null($module)) {
            $like .= '"module":"' . $module->getTag() . '"%';
        }
        $results = $this->tripleStore->getMatching(
            null,
            self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
            $like,
            'LIKE',
            '=',
            'LIKE'
        );
        
        $extraActivities = new ExtraActivityLogs() ;
        if ($results) {
            foreach ($results as $result) {
                $extraActivity = ExtraActivityLog::createFromJSON(
                    $result['value'],
                    $this->courseManager
                );
                if ($extraActivity) {
                    if (!$extraActivities->add($extraActivity)) {
                        // already present
                        $extraActivity = $extraActivities->get($extraActivity->getTag());
                    };
                    $extraActivity->addLearnerName($result['resource']) ;
                }
            }
        }
        return $extraActivities ;
    }
}
