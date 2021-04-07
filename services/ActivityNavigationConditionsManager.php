<?php


namespace YesWiki\Lms\Service;

use YesWiki\Bazar\Service\FormManager;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Module;
use YesWiki\Lms\Service\CourseManager;

class ActivityNavigationConditionsManager
{
    public const STATUS_LABEL = 'status';
    public const URL_LABEL = 'url';
    public const MESSAGE_LABEL = 'message';
    public const STATUS_CODE_OK =  0 ;
    public const STATUS_CODE_ERROR =  1 ;
    public const STATUS_CODE_NOT_OK =  2 ;

    protected $courseManager;
    protected $formManager;

    /**
     * LearnerManager constructor
     *
     * @param CourseManager $courseManager the injected CourseManager instance
     * @param FormManager $formManager the injected CourseManager instance
     */
    public function __construct(
        CourseManager $courseManager,
        FormManager $formManager
    ) {
        $this->courseManager = $courseManager;
        $this->formManager = $formManager;
    }

    /**
     * checkActivityNavigationConditions
     *
     * @param mixed $course, $course object or coursetag or null
     * @param mixed $module, $module object or moduletag or null
     * @param mixed $activity, $activity object or activitytag or null
     * @param array $conditions, when called from field to avoid infinite loop
     * @return [self::STATUS_LABEL => true|false,self::URL_LABEL => "https://...",self::MESSAGE_LABEL => <html for meesage>]
     */
    public function checkActivityNavigationConditions($course, $module, $activity, array $conditions = []): array
    {
        $data = $this->checkParams($course, $module, $activity, $conditions);
        if ($data[self::STATUS_LABEL] == self::STATUS_CODE_ERROR) {
            // error
            return $data;
        }
        unset($data[self::STATUS_LABEL]);

        /* set $conditions */
        if (empty($data['conditions'])) {
            $data = $this->findConditions($data);
            if ($data[self::STATUS_LABEL] == self::STATUS_CODE_ERROR) {
                // error
                return $data;
            }
        }

        return [
                self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::URL_LABEL => "",
                self::MESSAGE_LABEL => '<div>No Message</div>',
        ];
    }

    /** checks params
     * @param mixed $course, $course object or coursetag or null
     * @param mixed $module, $module object or moduletag or null
     * @param mixed $activity, $activity object or activitytag or null
     * @param array $conditions, when called from field to avoid infinite loop
     * @return [] [self::STATUS_LABEL=>0(OK)/1(error)/2(NOT OK),'course'=>$course,'module=>$module,'activity'=>$activity]
     */
    private function checkParams($course, $module, $activity, array $conditions):array
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

        return [
            self::STATUS_LABEL => self::STATUS_CODE_OK,
            'course' => $course,
            'module' => $module,
            'activity' => $activity,
            'conditions' => $conditions,
        ];
    }

    /** Find conditions
     * @param array $data ['course'=>$course,'module=>$module,'activity'=>$activity]
    * @param array ['course'=>$course,'module=>$module,'activity'=>$activity, 'conditions'=>$conditions]
     */
    private function findConditions(array $data): array
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
        
        $data[self::STATUS_LABEL] = self::STATUS_CODE_OK ;
        return $data;
    }
}
