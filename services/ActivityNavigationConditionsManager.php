<?php


namespace YesWiki\Lms\Service;

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Lms\CourseStructure;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Module;
use YesWiki\Lms\ModuleStatus;
use YesWiki\Lms\ActivityNavigationConditionsManagerResult;
use YesWiki\Lms\Field\ActivityNavigationField;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Lms\Service\QuizManager;
use YesWiki\Wiki;

class ActivityNavigationConditionsManager
{
    protected $courseManager;
    protected $learnerManager;
    protected $quizManager;
    protected $entryManager;
    protected $formManager;
    protected $wiki;

    /**
     * LearnerManager constructor
     *
     * @param CourseManager $courseManager the injected CourseManager instance
     * @param EntryManager $entryManager the injected EntryManager instance
     * @param FormManager $formManager the injected CourseManager instance
     * @param LearnerManager $learnerManager the injected CourseManager instance
     * @param QuizManager $quizManager the injected QuizManager instance
     * @param Wiki $wiki
     */
    public function __construct(
        CourseManager $courseManager,
        EntryManager $entryManager,
        FormManager $formManager,
        LearnerManager $learnerManager,
        QuizManager $quizManager,
        Wiki $wiki
    ) {
        $this->courseManager = $courseManager;
        $this->entryManager = $entryManager;
        $this->formManager = $formManager;
        $this->learnerManager = $learnerManager;
        $this->quizManager = $quizManager;
        $this->wiki = $wiki;

        // load the lms lib
        require_once LMS_PATH . 'libs/lms.lib.php';
    }

    /**
     * checkActivityNavigationConditions
     *
     * @param mixed $course, $course object or coursetag or null
     * @param mixed $module, $module object or moduletag or null
     * @param mixed $activity, $activity object or activitytag or null
     * @param mixed $conditions, when called from field to avoid infinite loop
     * @param bool $checkStatus
     * @param CourseStructure|null $nextCourseStructure
     * @return ActivityNavigationConditionsManagerResult
     */
    public function checkActivityNavigationConditions(
        $course,
        $module,
        $activity,
        $conditions = [],
        bool $checkStatus = true,
        ?CourseStructure $nextCourseStructure = null
    ): ActivityNavigationConditionsManagerResult {
        /* check params */
        try {
            $data = $this->checkParams($course, $module, $activity, $conditions);
        } catch (\Throwable $th) {
            $result = new ActivityNavigationConditionsManagerResult();
            $result->setError();
            $result->addMessage($th->getMessage());
            return $result;
        }

        /* get $conditions */
        if (empty($data['conditions'])) {
            try {
                $data = $this->getConditions($data);
            } catch (\Throwable $th) {
                $result = new ActivityNavigationConditionsManagerResult();
                $result->setError();
                $result->addMessage($th->getMessage());
                return $result;
            }
        }

        /* clean $data['conditions'] */
        $data = $this->cleanConditions($data);
        
        /* check conditions */
        $result = $this->checkConditions($data);
        
        if ($result->getStatus()) {
            if (is_null($nextCourseStructure)) {
                $nextCourseStructure = $this->courseManager->getNextActivityOrModule($data['course'], $data['module'], $data['activity']);
            }
            /* check status if all is OK*/
            if (!$nextCourseStructure) {
                $result->setError();
                $result->addMessage('Next activity or module not found in getNextActivityOrModule() for activity: \''.
                    $data['activity']->getTag().'\'!');
            } else {
                if ($checkStatus && !$data['learner']->isAdmin()) {
                    $result = $this->checkStatus($data, $result, $nextCourseStructure);
                }
                if ($result->getStatus()) {
                    $result->setURL($this->wiki->Href(
                        '',
                        $nextCourseStructure->getTag(),
                        ['course' => $data['course']->getTag()]+
                            (($nextCourseStructure instanceof Activity)
                                ?['module' => $data['module']->getTag()]:[]),
                        false
                    ));
                }
            }
        }

        return $result ;
    }

    /**
     * passActivityNavigationConditions
     *
     * @param mixed $course, $course object or coursetag or null
     * @param mixed $module, $module object or moduletag or null
     * @param mixed $activity, $activity object or activitytag or null
     * @param CourseStructure|null $nextCourseStructure
     * @return bool
     */
    public function passActivityNavigationConditions(
        $course,
        $module,
        $activity,
        ?CourseStructure $nextCourseStructure = null
    ):bool {
        return $this->checkActivityNavigationConditions(
            $course,
            $module,
            $activity,
            [],
            false,
            $nextCourseStructure
        )->getStatus();
    }

    /** checks params
     * @param mixed $course, $course object or coursetag or null
     * @param mixed $module, $module object or moduletag or null
     * @param mixed $activity, $activity object or activitytag or null
     * @param mixed $conditions, when called from field to avoid infinite loop
     * @return [] ['course'=>$course,'module=>$module,'activity'=>$activity]
     */
    private function checkParams($course, $module, $activity, $conditions):array
    {
        if (!$course) {
            throw new \Error('{course} should be defined !');
        }
        if (!($course instanceof Course) && !$course = $this->courseManager->getCourse(strval($course))) {
            throw new \Error('{course} should be a course or a course\'s tag !');
        }

        if (!$module) {
            throw new \Error('{module} should be defined !');
        }
        if ($module instanceof Module) {
            if (!$course->hasModule($module->getTag())) {
                throw new \Error('{module} \''.$module->getTag().'\' is not a module of the {course} \''.$course->getTag().'\' !');
            }
        } elseif (!$module = $this->courseManager->getModule(strval($module))) {
            throw new \Error('{module} should be a module or a module\'s tag !');
        } elseif (!$course->hasModule($module->getTag())) {
            throw new \Error('{module} \''.$module->getTag().'\' is not a module of the {course} \''.$course->getTag().'\' !');
        }

        if (!$activity) {
            throw new \Error('{activity} should be defined !');
        }

        if ($activity instanceof Activity) {
            if (!$module->hasActivity($activity->getTag())) {
                throw new \Error('{activity} \''.$activity->getTag().'\' is not an activity of the {module} \''.$module->getTag().'\' !');
            }
        } elseif (!$activity = $this->courseManager->getActivity(strval($activity))) {
            throw new \Error('{activity} should be an activity or an activity\'s tag !');
        } elseif (!$module->hasActivity($activity->getTag())) {
            throw new \Error('{activity} \''.$activity->getTag().'\' is not an activity of the {module} \''.$module->getTag().'\' !');
        }

        if (!$currentLearner = $this->learnerManager->getLearner()) {
            throw new \Error('You should be connected to check conditions !');
        }

        if (!is_array($conditions)) {
            $conditions = [];
        }

        return [
            'course' => $course,
            'module' => $module,
            'activity' => $activity,
            'conditions' => $conditions,
            'learner' => $currentLearner,
        ];
    }

    /** Get conditions
     * @param array $data ['course'=>$course,'module=>$module,'activity'=>$activity]
     * @return array ['course'=>$course,'module=>$module,'activity'=>$activity, 'conditions'=>$conditions]
     */
    private function getConditions(array $data): array
    {
        $formId = $data['activity']->getField('id_typeannonce');
        if (!$form = $this->formManager->getOne($formId)) {
            throw new \Error('Not possible to get form from $activity[\'id_typeannone\'] for \''.$activity->getTag().'\' !');
        }

        /* search ActivityNavigationField's propertyNmame */
        foreach ($form['prepared'] as $field) {
            if ($field instanceof ActivityNavigationField) {
                $propertyName = $field->getPropertyName();
                break;
            }
        }
        $data['conditions'] = (isset($propertyName)) ? ($data['activity']->getField($propertyName) ?? []):[];
        $data['conditions'] = !is_array($data['conditions']) ? [] : $data['conditions'];
        return $data;
    }

    /**
     * clean conditions
     * @param array $data
     * @return array $data
     */
    private function cleanConditions(array $data): array
    {
        $data['conditions'] = array_filter($data['conditions'], function ($item) use ($data) {
            return isset($item['condition']) && (
                !isset($item['scope']) || !is_array($item['scope']) || (
                    !empty(array_filter(
                        $item['scope'],
                        function ($scope_item) use ($data) {
                            return isset($scope_item['course']) && $scope_item['course'] == $data['course']->getTag() &&
                                (!isset($scope_item['module'])||(
                                    $scope_item['module'] == $data['module']->getTag()
                                ));
                        }
                    ))
                )
            );
        });
        return $data;
    }

    /**
     * check conditions
     * @param array $data
     * @return ActivityNavigationConditionsManagerResult
     */
    private function checkConditions(array $data): ActivityNavigationConditionsManagerResult
    {
        $result = new ActivityNavigationConditionsManagerResult();
        if (!empty($data['conditions'])) {
            foreach ($data['conditions'] as $condition) {
                switch ($condition['condition']) {
                    case ActivityNavigationField::LABEL_REACTION_NEEDED:
                        $result = $this->checkReactionNeeded($data, $result);
                        break;
                    case ActivityNavigationField::LABEL_QUIZ_PASSED:
                        $result = $this->checkQuizPassed($data, $result, $condition[ActivityNavigationField::LABEL_QUIZ_ID]);
                        break;
                    case ActivityNavigationField::LABEL_QUIZ_PASSED_MINIMUM_LEVEL:
                        $result = $this->checkQuizPassedMinimumLevel(
                            $data,
                            $result,
                            $condition[ActivityNavigationField::LABEL_QUIZ_ID],
                            $condition[ActivityNavigationField::LABEL_QUIZ_MINIMUM_LEVEL]
                        );
                        break;
                    case ActivityNavigationField::LABEL_FORM_FILLED:
                        $result = $this->checkFormFilled($data, $result, $condition[ActivityNavigationField::LABEL_FORM_ID]);
                        break;
                    default:
                        // unknown condition
                        $result->setError();
                        $result->addMessage('condition:\''.$condition['condition'].'\' is unknown  in activity: \''.
                            $data['activity']->getTag().'\'!');
                        break;
                }
            }
        }
        return $result;
    }

    /** checkReactionNeeded
     * @param array $data
     * @param ActivityNavigationConditionsManagerResult $result
     * @return ActivityNavigationConditionsManagerResult
     */
    private function checkReactionNeeded(array $data, ActivityNavigationConditionsManagerResult $result): ActivityNavigationConditionsManagerResult
    {
        // get Reactions
        $reactions = $data['learner'] ? getUserReactionOnPage($data['activity']->getTag(), $data['learner']->getUserName()) : null;
        if (empty($reactions)) {
            $result->setNotOk();
            $result->addMessage(_t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_REACTION_NEEDED_HELP'));
        } else {
            $result->setReactionsNeeded();
        }
        return $result;
    }
    
    /** checkQuizPassed
     * @param array $data
     * @param ActivityNavigationConditionsManagerResult $result
     * @param string $quizId
     * @return ActivityNavigationConditionsManagerResult
     */
    private function checkQuizPassed(array $data, ActivityNavigationConditionsManagerResult $result, string $quizId): ActivityNavigationConditionsManagerResult
    {
        // get quizResults
        $quizResults = $this->quizManager->getQuizResults(
            $this->learnerManager->getLearner()->getUserName(),
            $data['course']->getTag(),
            $data['module']->getTag(),
            $data['activity']->getTag(),
            !empty($quizId) ? $quizId : null
        );
        switch ($quizResults[QuizManager::STATUS_LABEL]) {
            case QuizManager::STATUS_CODE_OK:
                break;
            case QuizManager::STATUS_CODE_NO_RESULT:
                $result->setNotOk();
                $result->addMessage(
                    !empty($quizId)
                    ? _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED_HELP').' \''.$quizId.'\''
                    : _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED_HELP_FOR_ANY')
                );
                break;
            case QuizManager::STATUS_CODE_ERROR:
            default:
                $result->setError();
                $result->addMessage($quizResults[QuizManager::MESSAGE_LABEL]);
                break;
        }
        return $result;
    }

    /** checkQuizPassedMinimumLevel
     * @param array $data
     * @param ActivityNavigationConditionsManagerResult $result
     * @param string $quizId
     * @param string $QuizMinimumLevel
     * @return ActivityNavigationConditionsManagerResult
     */
    private function checkQuizPassedMinimumLevel(array $data, ActivityNavigationConditionsManagerResult $result, string $quizId, string $QuizMinimumLevel): ActivityNavigationConditionsManagerResult
    {
        // get quizResults
        $quizResults = $this->quizManager->getQuizResults(
            $this->learnerManager->getLearner()->getUserName(),
            $data['course']->getTag(),
            $data['module']->getTag(),
            $data['activity']->getTag(),
            !empty($quizId) ? $quizId : null
        );
        switch ($quizResults[QuizManager::STATUS_LABEL]) {
            case QuizManager::STATUS_CODE_OK:
                // check level
                $levelPassed= false;
                foreach ($quizResults[QuizManager::RESULTS_LABEL] as $triple) {
                    if (floatval($triple[QuizManager::RESULT_LABEL]) >= floatval($QuizMinimumLevel)) {
                        $levelPassed=true;
                    }
                }
                if (!$levelPassed) {
                    $result->setNotOk();
                    $result->addMessage(
                        (
                            !empty($quizId)
                            ? _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED_HELP').' \''.$quizId.'\''
                            : _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED_HELP_FOR_ANY')
                        )._t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_MINIMUM_LEVEL_HELP').' '.$QuizMinimumLevel.' %'
                    );
                }
                break;
            case QuizManager::STATUS_CODE_NO_RESULT:
                $result->setNotOk();
                $result->addMessage(
                    (
                        !empty($quizId)
                        ? _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED_HELP').' \''.$quizId.'\''
                        : _t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_QUIZ_PASSED_HELP_FOR_ANY')
                    )
                );
                break;
            case QuizManager::STATUS_CODE_ERROR:
            default:
                $result->setError();
                $result->addMessage($quizResults[QuizManager::MESSAGE_LABEL]);
                break;
        }
        return $result;
    }

    /** checkFormFilled
     * @param array $data
     * @param ActivityNavigationConditionsManagerResult $result
     * @param array $formId
     * @return ActivityNavigationConditionsManagerResult
     */
    private function checkFormFilled(array $data, ActivityNavigationConditionsManagerResult $result, string $formId): ActivityNavigationConditionsManagerResult
    {
        // get entries
        $entries = $this->entryManager->search([
            'formsIds' => [$formId],
            'user' => $this->learnerManager->getLearner()->getUserName()
        ]);
        if (empty($entries)) {
            $form = $this->formManager->getOne($formId);
            $result->setNotOk();
            $result->addMessage(_t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_FORM_FILLED_HELP')
                    .' \''.(!empty($form)?$form['bn_label_nature']:$formId).'\'');
        }
        return $result;
    }

    /** checkStatus
     * @param array $data
     * @param ActivityNavigationConditionsManagerResult $result
     * @param CourseStructure $nextCourseStructure
     * @return ActivityNavigationConditionsManagerResult
     */
    private function checkStatus(array $data, ActivityNavigationConditionsManagerResult $result, CourseStructure $nextCourseStructure): ActivityNavigationConditionsManagerResult
    {
        if (!empty($data['activity'])) {
            if ($nextCourseStructure instanceof Module) {
                if (!$this->courseManager->checkModuleCanBeOpenedByLearner($data['learner'], $data['course'], $nextCourseStructure, false)) {
                    switch ($nextCourseStructure->getStatus($data['course'])) {
                        case ModuleStatus::CLOSED:
                            $result->setNotOk();
                            $result->addMessage(_t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_NEXT_MODULE_CLOSED'));
                            break;
                        case ModuleStatus::TO_BE_OPEN:
                            $result->setNotOk();
                            $result->addMessage(_t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_NEXT_MODULE_TO_BE_OPEN'));
                            break;
                        case ModuleStatus::NOT_ACCESSIBLE:
                        case ModuleStatus::OPEN:
                            $result->setNotOk();
                            $result->addMessage(_t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_NEXT_MODULE_NOT_ACCESSIBLE'));
                            break;
                        default:
                            $result->setError();
                            $result->addMessage('The module status \''.$nextCourseStructure->getStatus($data['course']).'\' is not defined !');
                            break;
                    }
                }
            } elseif ($nextCourseStructure instanceof Activity) {
                if (!$this->courseManager->checkActivityCanBeOpenedByLearner($data['learner'], $data['course'], $data['module'], $nextCourseStructure, false)) {
                    $result->setNotOk();
                    $result->addMessage(_t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_NEXT_ACTIVITY_NOT_ACCESSIBLE'));
                }
            }
        }
        
        return $result;
    }
}
