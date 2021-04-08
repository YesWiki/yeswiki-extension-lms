<?php


namespace YesWiki\Lms\Service;

use YesWiki\Bazar\Service\FormManager;
use YesWiki\Lms\CourseStructure;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Module;
use YesWiki\Lms\Field\ActivityNavigationField;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Wiki;

class ActivityNavigationConditionsManager
{
    public const STATUS_LABEL = 'status';
    public const URL_LABEL = 'url';
    public const MESSAGE_LABEL = 'message';
    public const STATUS_CODE_OK =  0 ;
    public const STATUS_CODE_ERROR =  1 ;
    public const STATUS_CODE_NOT_OK =  2 ;
    public const STATUS_CODE_OK_REACTIONS_NEEDED =  3 ;

    protected $courseManager;
    protected $learnerManager;
    protected $formManager;
    protected $wiki;

    /**
     * LearnerManager constructor
     *
     * @param CourseManager $courseManager the injected CourseManager instance
     * @param FormManager $formManager the injected CourseManager instance
     * @param LearnerManager $learnerManager the injected CourseManager instance
     * @param Wiki $wiki
     */
    public function __construct(
        CourseManager $courseManager,
        FormManager $formManager,
        LearnerManager $learnerManager,
        Wiki $wiki
    ) {
        $this->courseManager = $courseManager;
        $this->formManager = $formManager;
        $this->learnerManager = $learnerManager;
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
     * @return [self::STATUS_LABEL => true|false,self::URL_LABEL => "https://...",self::MESSAGE_LABEL => <html for meesage>]
     */
    public function checkActivityNavigationConditions($course, $module, $activity, $conditions = []): array
    {
        /* check params */
        $data = $this->checkParams($course, $module, $activity, $conditions);
        if ($data[self::STATUS_LABEL] == self::STATUS_CODE_ERROR) {
            // error
            return $data;
        }
        unset($data[self::STATUS_LABEL]);

        /* get $conditions */
        if (empty($data['conditions'])) {
            $data = $this->getConditions($data);
            if ($data[self::STATUS_LABEL] == self::STATUS_CODE_ERROR) {
                // error
                return $data;
            }
        }

        /* clean $data['conditions'] */
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
        
        /* check conditions */
        $result = [
            self::STATUS_LABEL => self::STATUS_CODE_OK,
            self::URL_LABEL => '',
            self::MESSAGE_LABEL => ''
        ];
        if (!empty($data['conditions'])) {
            foreach ($data['conditions'] as $condition) {
                switch ($condition['condition']) {
                    case ActivityNavigationField::LABEL_REACTION_NEEDED:
                        $result = $this->checkReactionNeeded($data, $result);
                        break;
                    case ActivityNavigationField::LABEL_QUIZZ_DONE:
                    default:
                        // unknown condition
                        $result[self::STATUS_LABEL] = self::STATUS_CODE_ERROR;
                        $result[self::MESSAGE_LABEL] .= '<div>condition:\''.$condition['condition'].'\' is unknown  in activity: \''.
                            $data['activity']->getTag().'\'!</div>';
                        break;
                }
            }
        }

        if (in_array($result[self::STATUS_LABEL], [self::STATUS_CODE_OK,self::STATUS_CODE_OK_REACTIONS_NEEDED])) {
            if ($nextStructure = $this->getNextActivityOrModule(
                $data['course'],
                $data['module'],
                $data['activity']
            )) {
                $result[self::URL_LABEL] = $this->wiki->Href(
                    '',
                    $nextStructure->getTag(),
                    ['course' => $data['course']->getTag()]+
                        (($nextStructure instanceof Activity)
                            ?['module' => $data['module']->getTag()]:[]),
                    false
                );
            } else {
                $result[self::STATUS_LABEL] = self::STATUS_CODE_ERROR;
                $result[self::MESSAGE_LABEL] .= '<div>Next activity or module not found in getNextActivityOrModule() in activity: \''.
                    $data['activity']->getTag().'\'!</div>';
            }
        }

        return $result;
    }

    /** checks params
     * @param mixed $course, $course object or coursetag or null
     * @param mixed $module, $module object or moduletag or null
     * @param mixed $activity, $activity object or activitytag or null
     * @param mixed $conditions, when called from field to avoid infinite loop
     * @return [] [self::STATUS_LABEL=>0(OK)/1(error)/2(NOT OK),'course'=>$course,'module=>$module,'activity'=>$activity]
     */
    private function checkParams($course, $module, $activity, $conditions):array
    {
        if (!$course) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::MESSAGE_LABEL => '{course} should be defined !'];
        }
        if (!($course instanceof Course) && !$course = $this->courseManager->getCourse(strval($course))) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::MESSAGE_LABEL => '{course} should be a course or a course\'s tag !'];
        }

        if (!$module) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::MESSAGE_LABEL => '{module} should be defined !'];
        }
        if ($module instanceof Module) {
            if (!$course->hasModule($module->getTag())) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                    self::MESSAGE_LABEL => '{module} \''.$module->getTag().'\' is not a module of the {course} \''.$course->getTag().'\' !'];
            }
        } elseif (!$module = $this->courseManager->getModule(strval($module))) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::MESSAGE_LABEL => '{module} should be a module or a module\'s tag !'];
        } elseif (!$course->hasModule($module->getTag())) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::MESSAGE_LABEL => '{module} \''.$module->getTag().'\' is not a module of the {course} \''.$course->getTag().'\' !'];
        }

        if (!$activity) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::MESSAGE_LABEL => '{activity} should be defined !'];
        }

        if ($activity instanceof Activity) {
            if (!$module->hasActivity($activity->getTag())) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                    self::MESSAGE_LABEL => '{activity} \''.$activity->getTag().'\' is not an activity of the {module} \''.$module->getTag().'\' !'];
            }
        } elseif (!$activity = $this->courseManager->getActivity(strval($activity))) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::MESSAGE_LABEL => '{activity} should be an activity or an activity\'s tag !'];
        } elseif (!$module->hasActivity($activity->getTag())) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::MESSAGE_LABEL => '{activity} \''.$activity->getTag().'\' is not an activity of the {module} \''.$module->getTag().'\' !'];
        }

        if (!is_array($conditions)) {
            $conditions = [];
        }

        return [
            self::STATUS_LABEL => self::STATUS_CODE_OK,
            'course' => $course,
            'module' => $module,
            'activity' => $activity,
            'conditions' => $conditions,
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
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::MESSAGE_LABEL => 'Not possible to get form from $activity[\'id_typeannone\'] for \''.$activity->getTag().'\' !'];
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

        $data[self::STATUS_LABEL] = self::STATUS_CODE_OK ;
        return $data;
    }

    /** getNextActivityOrModule
     * @param Course $course
     * @param Module $module
     * @param Activity $activity
     * @return CourseStructure next activity or module
     */
    public function getNextActivityOrModule(Course $course, Module $module, Activity $activity): ?CourseStructure
    {
        if ($activity->getTag() == $module->getLastActivityTag()) {
            if ($module->getTag() != $course->getLastModuleTag()) {
                return $course->getNextModule($module->getTag());
                // if the current page is the last activity of the module and the module is not the last one,
                // the next link is to the next module entry
                // (no next button is showed for the last activity of the last module)
            }
        } else {
            // otherwise, the current activity is not the last of the module and the next link is set to the next activity
            return $module->getNextActivity($activity->getTag());
        }
        return null;
    }

    /** checkReactionNeeded
     * @param array $data
     * @param array $result
     * @return array [self::STATUS_LABEL => status,self::MESSAGE_LABEL => '...']
     */
    private function checkReactionNeeded(array $data, array $result): array
    {
        // get Reactions
        $reactions = getUserReactionOnPage($data['activity']->getTag(), $this->learnerManager->getLearner()->getUserName());
        $result[self::STATUS_LABEL] = ($result[self::STATUS_LABEL] != self::STATUS_CODE_ERROR) ?
            ((empty($reactions))? self::STATUS_CODE_NOT_OK : self::STATUS_CODE_OK_REACTIONS_NEEDED) : $result[self::STATUS_LABEL];
        $result[self::MESSAGE_LABEL] .= (empty($reactions))? '<div>'._t('LMS_ACTIVITY_NAVIGATION_CONDITIONS_REACTION_NEEDED_HELP').'</div>':'';
        return $result;
    }
}
