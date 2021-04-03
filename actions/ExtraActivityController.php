<?php

namespace YesWiki\Lms\Controller;

use YesWiki\Core\YesWikiController;
use YesWiki\Wiki;
use YesWiki\Lms\Course;
use YesWiki\Lms\ExtraActivityLog;
use YesWiki\Lms\Module;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\ExtraActivityManager;

class ExtraActivityController extends YesWikiController
{
    protected $arguments;
    protected $courseManager;
    protected $extraActivityManager;
    protected $wiki;

    /**
     * ExtraActivityController constructor
     * @param CourseManager $courseManager the injected CourseManager instance
     * @param ExtraActivityManager $extraActivityManager the injected ExtraActivityManager instance
     * @param Wiki $wiki
     */
    public function __construct(
        CourseManager $courseManager,
        ExtraActivityManager $extraActivityManager,
        Wiki $wiki
    ) {
        $this->courseManager = $courseManager;
        $this->extraActivityManager = $extraActivityManager;
        $this->arguments = [] ;
        $this->wiki = $wiki;
    }

    
    /**
     * Setter for the arguments property
     * @param array $arguments
     */
    public function setArguments(array &$arguments): void
    {
        $this->arguments = $this->formatArguments($arguments);
    }

    /**
     * format arguments property
     * @param array $arguments
     * @return string html to display
     */
    protected function formatArguments($arg)
    {
        return [
            'mode' => (!empty($_GET['mode'])) ? $_GET['mode']
                        : (
                            (!empty($_POST['mode'])) ? $_POST['mode']
                            : ((!empty($arg['mode'])) ? $arg['mode']: null)
                        ) ,
            'extra_activity_mode' => $this->wiki->config['lms_config']['extra_activity_mode'] ?? false,
            'course' => (!empty($_GET['course'])) ? $_GET['course']
                    : (
                        (!empty($_POST['course'])) ? $_POST['course']
                        : ((!empty($arg['course'])) ? $arg['course']: null)
                    ) ,
            'module' => $_REQUEST['module'] ?? null ,
            'tag' => $_REQUEST['tag'] ?? null ,
            'learner' => $_REQUEST['learner'] ?? null ,
            'confirm' => $_REQUEST['confirm'] ?? null ,
        ];
    }
    
    /**
     * run the controller
     * @param array $learners ['username1','username2',...]
     */
    public function run(array $learners): ?string
    {
        if (!$this->arguments['extra_activity_mode']) {
            return null;
        }
        switch ($this->arguments['mode']) {
            case 'add':
                return $this->edit($learners);
                break ;
            case 'edit':
                $extraActivity = $this->extraActivityManager->getExtraActivity(
                    $this->arguments['tag']
                );
                return $this->edit(
                    $learners,
                    ($extraActivity) ? ['extraActivity' => $extraActivity] : []
                );
                break ;
            case 'save':
                if ($this->extraActivityManager->saveExtraActivity($_POST)) {
                    $this->wiki->Redirect($this->wiki->Href(null, null, [
                        'course' => $this->arguments['course'],
                        'module' => $this->arguments['module']
                    ], false));
                } else {
                    return $this->render(
                        '@templates/alert-message.twig',
                        [
                            'type' => 'danger',
                            'message' => _t('LMS_EXTRA_ACTIVITY_ERROR_AT_SAVE') . ($_POST['title'] ?? !!!'$_POST[\'title\'] not set!!!')
                        ]
                    )
                    . $this->render('@lms/extra-activity-backlink.twig', [
                        'course' => $this->arguments['course'],
                        'module' => $this->arguments['module']
                    ]);
                }
                break ;
            case 'remove':
                if ($this->arguments['confirm'] != 'yes') {
                    return $this->render(
                        '@lms/extra-activity-confirm.twig',
                        [
                            'message' => _t('LMS_EXTRA_ACTIVITY_REMOVE_LEARNER')
                                    .'"'.$this->arguments['learner'].'"'
                                    ._t('LMS_EXTRA_ACTIVITY_REMOVE_LEARNER_END')
                                    .'"'.$this->arguments['tag'].'"'
                                    ,
                            'course' => $this->arguments['course'],
                            'module' => $this->arguments['module'],
                            'tag' => $this->arguments['tag'],
                            'learner' => $this->arguments['learner'],
                            'mode' => $this->arguments['mode'],
                        ]
                    );
                } elseif ($this->extraActivityManager->deleteExtraActivity($this->arguments['tag'], $this->arguments['learner'])) {
                    $this->wiki->Redirect($this->wiki->Href(null, null, [
                        'course' => $this->arguments['course'],
                        'module' => $this->arguments['module'],
                        'learner' => $this->arguments['learner']
                    ], false));
                } else {
                    return $this->render(
                        '@templates/alert-message.twig',
                        [
                            'type' => 'danger',
                            'message' => _t('LMS_EXTRA_ACTIVITY_ERROR_AT_REMOVE')
                            .'"'.($this->arguments['learner'] ?? '!!!$_GET[\'learner\'] not set!!').'"'
                            ._t('LMS_EXTRA_ACTIVITY_REMOVE_LEARNER_END')
                            .'"'.($this->arguments['tag'] ?? '!!!$_GET[\'tag\'] not set!!').'"'
                        ]
                    )
                    . $this->render('@lms/extra-activity-backlink.twig', [
                        'course' => $this->arguments['course'],
                        'module' => $this->arguments['module']
                    ]);
                }
                // no break
            case 'delete':
                if ($this->arguments['confirm'] != 'yes') {
                    return $this->render(
                        '@lms/extra-activity-confirm.twig',
                        [
                            'message' => _t('LMS_EXTRA_ACTIVITY_DELETE').'"'.$this->arguments['tag'].'"',
                            'course' => $this->arguments['course'],
                            'module' => $this->arguments['module'],
                            'tag' => $this->arguments['tag'],
                            'mode' => $this->arguments['mode'],
                        ]
                    );
                } elseif ($this->extraActivityManager->deleteExtraActivity($this->arguments['tag'])) {
                    $this->wiki->Redirect($this->wiki->Href(null, null, [
                        'course' => $this->arguments['course'],
                        'module' => $this->arguments['module']
                    ], false));
                } else {
                    return $this->render(
                        '@templates/alert-message.twig',
                        [
                            'type' => 'danger',
                            'message' => _t('LMS_EXTRA_ACTIVITY_ERROR_AT_DELETE') .
                                ($this->arguments['tag'] ?? '!!!$_GET[\'tag\'] not set!!')
                        ]
                    )
                    . $this->render('@lms/extra-activity-backlink.twig', [
                        'course' => $this->arguments['course'],
                        'module' => $this->arguments['module']
                    ]);
                }
                // no break
            default:
                return null ;
        }
    }

    /**
     * render the edit form
     * @param array $learners ['username1','username2',...]
     * @param array $params ['index' => 'value','other_index'=>'value'] whould be merged with default
     * @return string html to display
     */
    private function edit(array $learners, array $params = []): string
    {
        $course = $this->courseManager->getCourse($this->arguments['course']) ;
        $modules = [];
        foreach ($course->getModules() as $module) {
            $modules[$module->getTag()] = $module->getTitle();
        }
        return $this->render(
            '@lms/extra-activity-form.twig',
            array_merge([
                'course' => $course,
                'module' => $this->arguments['module'],
                'modules' => $modules,
                'learners' => array_map(function ($learner) {
                    return $learner->getFullName() ;
                }, $learners),
            ], $params)
        );
    }
}
