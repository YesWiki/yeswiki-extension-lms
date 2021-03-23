<?php

namespace YesWiki\Lms\Controller;

use YesWiki\Core\YesWikiController;
use YesWiki\Lms\Course;
use YesWiki\Lms\ExtraActivityLog;
use YesWiki\Lms\Module;

class ExtraActivityController extends YesWikiController
{
    // protected $entryManager;
    // protected $courseManager;
    // protected $learnerManager;
    // protected $config;
    protected $arguments;

    /**
     * CourseController constructor
     * @param EntryManager $entryManager the injected EntryManager instance
     * @param CourseManager $courseManager the injected CourseManager instance
     * @param LearnerManager $learnerManager the injected LearnerManager instance
     * @param ParameterBagInterface $config the injected Wiki instance
     */
    public function __construct(
        // EntryManager $entryManager,
        // CourseManager $courseManager,
        // LearnerManager $learnerManager,
        // ParameterBagInterface $config
    ) {
        // $this->entryManager = $entryManager;
        // $this->courseManager = $courseManager;
        // $this->learnerManager = $learnerManager;
        // $this->config = $config->all();
        $this->arguments = [] ;
    }

    
    /**
     * Setter for the arguments property
     * @param array $arguments
     */
    public function setArguments(array &$arguments): void
    {
        $this->arguments = $this->formatArguments($arguments);
    }

    protected function formatArguments($arg)
    {
        return [
            'mode' => (!empty($_GET['extraactivitymode'])) ? $_GET['extraactivitymode']
                        : (
                            (!empty($_POST['extraactivitymode'])) ? $_POST['extraactivitymode']
                            : ((!empty($arg['extraactivitymode'])) ? $arg['extraactivitymode']: null)
                        ) ,
            'testmode' => $this->wiki->config['lms_config']['extra_activity_mode'] ?? false,
        ];
    }
    
    public function run(): ?string
    {
        if (!$this->arguments['testmode']) {
            return null;
        }
        switch ($this->arguments['mode']) {
            case 'add':
                return $this->render(
                    '@lms/extra-activity-form.twig',
                    [
                    ]
                );
                break ;
            case 'edit':
            case 'remove':
            case 'delete':
            default:
                return null ;
        }
    }

    /** Manager part **/
    /* TODO ? move in manager ? */
    public function getExtraActivities(Course $course, Module $module = null): array
    {
        if (!$this->arguments['testmode']) {
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
