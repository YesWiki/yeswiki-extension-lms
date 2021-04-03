<?php

use YesWiki\Core\YesWikiAction;
use YesWiki\Lms\Course;
use YesWiki\Lms\ExtraActivityLog;
use YesWiki\Lms\Module;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\ExtraActivityManager;
use YesWiki\Lms\Service\LearnerManager;

class ExtraActivityAction extends YesWikiAction
{
    protected $courseManager;
    protected $extraActivityManager;
    protected $learnerManager;
    
    /**
     * format arguments property
     * @param array $arguments
     * @return array args
     */
    protected function formatArguments($arg)
    {
        return [
            'mode' => $_REQUEST['extra_activity_mode'] ?? null ,
            'extra_activity_enabled' => $this->wiki->config['lms_config']['extra_activity_enabled'] ?? false,
            'course' => $_REQUEST['course'] ?? null ,
            'module' => $_REQUEST['module'] ?? null ,
            'tag' => $_REQUEST['tag'] ?? null ,
            'learner' => $_REQUEST['learner'] ?? null ,
            'confirm' => $_REQUEST['confirm'] ?? null ,
            'learners' => $arg['learners'] ?? [],
        ];
    }
    
    /**
     * run the controller
     * @return string|null null if nothing to do
     */
    public function run(): ?string
    {
        if (!$this->arguments['extra_activity_enabled']) {
            return null;
        }

        $this->courseManager = $this->getService(CourseManager::class);
        $this->extraActivityManager = $this->getService(ExtraActivityManager::class);
        $this->learnerManager = $this->getService(LearnerManager::class);

        $currentLearner = $this->learnerManager->getLearner();
        if (!$currentLearner || !$currentLearner->isAdmin()) {
            if (empty($this->arguments['calledBy'])) {
                // reserved only to the admins
                return $this->render("@templates/alert-message.twig", [
                    'type' => 'danger',
                    'message' => _t('ACLS_RESERVED_FOR_ADMINS') . ' (extraactivity)'
                ]);
            } else {
                return null;
            }
        }

        switch ($this->arguments['mode']) {
            case 'add':
                return $this->edit();
                break ;
            case 'edit':
                $extraActivity = $this->extraActivityManager->getExtraActivityLog(
                    $this->arguments['tag']
                );
                return $this->edit(
                    ($extraActivity) ? ['extraActivity' => $extraActivity] : []
                );
                break ;
            case 'save':
                return $this->save();
                break ;
            case 'remove':
                return $this->remove();
                break;
            case 'delete':
                return $this->delete();
                break;
            default:
                return null ;
        }
    }

    /**
     * render the edit form
     * @param array $params ['index' => 'value','other_index'=>'value'] whould be merged with default
     * @return string html to display
     */
    private function edit(array $params = []): string
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
                }, $this->arguments['learners']),
            ], $params)
        );
    }

    /**
     * render the save extraactivity
     * @return string html to display
     */
    private function save(): string
    {
        try {
            if ($this->extraActivityManager->saveExtraActivity($_POST)) {
                $this->wiki->Redirect($this->wiki->Href(null, null, [
                    'course' => $this->arguments['course'],
                    'module' => $this->arguments['module']
                ], false));
            } else {
                throw new Exception("");
            }
        } catch (Throwable $t) {
            return $this->render(
                '@templates/alert-message.twig',
                [
                    'type' => 'danger',
                    'message' => _t('LMS_EXTRA_ACTIVITY_ERROR_AT_SAVE') .
                       ($_POST['title'] ?? '!!!$_POST[\'title\'] not set!!!') . '<br>'
                       .$t->getMessage()
                ]
            )
            . $this->render('@lms/extra-activity-backlink.twig', [
                'course' => $this->arguments['course'],
                'module' => $this->arguments['module']
            ]);
        }
    }

    /**
     * remove a learner
     * @return string html to display
     */
    private function remove(): string
    {
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
        } else {
            try {
                if ($this->extraActivityManager->deleteExtraActivity($this->arguments['tag'], $this->arguments['learner'])) {
                    $this->wiki->Redirect($this->wiki->Href(null, null, [
                        'course' => $this->arguments['course'],
                        'module' => $this->arguments['module'],
                        'learner' => $this->arguments['learner']
                    ], false));
                } else {
                    throw new Exception("");
                }
            } catch (Throwable $t) {
                return $this->render(
                    '@templates/alert-message.twig',
                    [
                        'type' => 'danger',
                        'message' => _t('LMS_EXTRA_ACTIVITY_ERROR_AT_REMOVE')
                        .'"'.($this->arguments['learner'] ?? '!!!$_GET[\'learner\'] not set!!!').'"'
                        ._t('LMS_EXTRA_ACTIVITY_REMOVE_LEARNER_END')
                        .'"'.($this->arguments['tag'] ?? '!!!$_GET[\'tag\'] not set!!!').'" <br>'.
                        $t->getMessage()
                    ]
                )
                . $this->render('@lms/extra-activity-backlink.twig', [
                    'course' => $this->arguments['course'],
                    'module' => $this->arguments['module']
                ]);
            }
        }
    }

    private function delete(): string
    {
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
        } else {
            try {
                if ($this->extraActivityManager->deleteExtraActivity($this->arguments['tag'])) {
                    $this->wiki->Redirect($this->wiki->Href(null, null, [
                        'course' => $this->arguments['course'],
                        'module' => $this->arguments['module']
                    ], false));
                } else {
                    throw new Exception("");
                }
            } catch (Throwable $t) {
                return $this->render(
                    '@templates/alert-message.twig',
                    [
                        'type' => 'danger',
                        'message' => _t('LMS_EXTRA_ACTIVITY_ERROR_AT_DELETE') .
                            ($this->arguments['tag'] ?? '!!!$_GET[\'tag\'] not set!!!') . '<br>'
                            . $t->getMessage()
                    ]
                )
                . $this->render('@lms/extra-activity-backlink.twig', [
                    'course' => $this->arguments['course'],
                    'module' => $this->arguments['module']
                ]);
            }
        }
    }
}
