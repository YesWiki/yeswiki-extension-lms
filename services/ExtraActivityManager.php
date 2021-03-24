<?php


namespace YesWiki\Lms\Service;

use YesWiki\Core\Service\TripleStore;
use YesWiki\Wiki;
use YesWiki\Lms\ExtraActivityLog ;
use YesWiki\Lms\Module ;
use YesWiki\Lms\Course ;
use YesWiki\Lms\Controller\ExtraActivityController ;

class ExtraActivityManager
{
    protected const LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY =  'https://yeswiki.net/vocabulary/lms-extra-activity' ;

    protected $tripleStore;
    protected $extraActivityController;
    protected $wiki;

    /**
     * LearnerManager constructor
     *
     * @param TripleStore $tripleStore the injected TripleStore instance
     * @param ExtraActivityController $extraActivityController the injected ExtraActivityController instance
     * @param Wiki $wiki
     */
    public function __construct(
        TripleStore $tripleStore,
        ExtraActivityController $extraActivityController,
        Wiki $wiki
    ) {
        $this->tripleStore = $tripleStore;
        $this->extraActivityController = $extraActivityController;
        $this->wiki = $wiki;
    }

    /**
     * Get the Extra-activities of a courseStructure
     *
     * @return [] the courseStructure's extraActivities
     */
    public function getExtraActivities(Course $course, Module $module = null): array
    {
        if (!$this->extraActivityController->getTestMode()) {
            return [];
        }

        if (!$module) {
            return [new ExtraActivityLog(
                'TagDeTestExtra',
                'Webinaire : Titre de test',
                '',
                new \DateTime('2000-01-01'),
                new \DateInterval('PT1H3M2S'),
                $course
            )] ;
        } else {
            return [new ExtraActivityLog(
                'TagDeTestExtra',
                'Webinaire : Titre de test',
                'BazaR',
                new \DateTime('2000-01-01'),
                new \DateInterval('PT1H3M2S'),
                $course,
                $module
            ),
            new ExtraActivityLog(
                'TagDeTestExtra2',
                'Atelier : Titre de test',
                '',
                new \DateTime('2000-01-02'),
                new \DateInterval('PT3H3M2S'),
                $course,
                $module
            ),
            new ExtraActivityLog(
                'TagDeTestExtra3',
                'Formation : Titre de test',
                'https://yeswiki.net',
                new \DateTime('2000-04-02'),
                new \DateInterval('P2DT3H3M2S'),
                $course,
                $module
            )] ;
        }
    }
}
