<?php

namespace YesWiki\Lms\Controller;

use YesWiki\Core\YesWikiController;
use YesWiki\Lms\Course;
use YesWiki\Lms\ExtraActivityLog;
use YesWiki\Lms\Module;

class ExtraActivityController extends YesWikiController
{
    protected $arguments;

    /**
     * ExtraActivityController constructor
     */
    public function __construct(
    ) {
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
            'course' => (!empty($_GET['course'])) ? $_GET['course']
                    : (
                        (!empty($_POST['course'])) ? $_POST['course']
                        : ((!empty($arg['course'])) ? $arg['course']: null)
                    ) ,
            'module' => $_REQUEST['module'] ?? null ,
            'activity' => $_REQUEST['activity'] ?? null ,
            'tag' => $_REQUEST['extraactivityid'] ?? null ,
            'learnerName' => $_REQUEST['extraactivitylearner'] ?? null ,
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
                    '@templates/alert-message.twig',
                    [
                        'type' => 'warning',
                        'message' => 'Mode test : création d\'une  activité. Non fonctionnel !! '
                    ]
                ) . $this->render(
                    '@lms/extra-activity-form.twig',
                    [
                    ]
                );
                break ;
            case 'edit':
                return $this->render(
                    '@templates/alert-message.twig',
                    [
                        'type' => 'info',
                        'message' => 'Mode test : édition de l\'activité : '. $this->arguments['tag']
                    ]
                );
                break ;
            case 'remove':
                return $this->render(
                    '@templates/alert-message.twig',
                    [
                        'type' => 'info',
                        'message' => 'Mode test : retrait de '.$this->arguments['learnerName'].'  de l\'activité : '. $this->arguments['tag']
                    ]
                );
            case 'delete':
                return $this->render(
                    '@templates/alert-message.twig',
                    [
                        'type' => 'info',
                        'message' => 'Mode test : suppression  de l\'activité : '. $this->arguments['tag']
                    ]
                );
            default:
                return null ;
        }
    }

    public function getTestMode()
    {
        return (isset($this->arguments['testmode']) && $this->arguments['testmode']) ;
    }
}
