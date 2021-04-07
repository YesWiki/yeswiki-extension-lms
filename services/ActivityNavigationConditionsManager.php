<?php


namespace YesWiki\Lms\Service;

use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Module;
use YesWiki\Lms\Service\CourseManager;

class ActivityNavigationConditionsManager
{
    public const STATUS_LABEL = 'conditions_passed';
    public const URL_LABEL = 'url';
    public const MESSAGE_LABEL = 'message';
    public const STATUS_CODE_OK =  true ;
    public const STATUS_CODE_ERROR =  false ;

    protected $courseManager;

    /**
     * LearnerManager constructor
     *
     * @param CourseManager $courseManager the injected CourseManager instance
     */
    public function __construct(
        CourseManager $courseManager
    ) {
        $this->courseManager = $courseManager;
    }

    /**
     * checkActivityNavigationConditions
     *
     * @param mixed $course, $course object or coursetag or null
     * @param mixed $module, $module object or moduletag or null
     * @param mixed $activity, $activity object or activitytag or null
     * @param mixed $entry, entry data of activity (to avoid loop in activitynavigationfield) or null if generate it
     * @return [self::STATUS_LABEL => true|false,self::URL_LABEL => "https://...",self::MESSAGE_LABEL => <html for meesage>]
     */
    public function checkActivityNavigationConditions($course, $module, $activity, $entry = null): array
    {
        $data = $this->checkParams($course, $module, $activity, $entry);
        if (!$data[self::STATUS_LABEL]) {
            // error
            return $data;
        }
        unset($data[self::STATUS_LABEL]);

        /* set $entry */
        if (empty($data['entry'])) {
            $data['entry'] = $data['activity']->getFields();
        }

        return [
                self::STATUS_LABEL => false,
                self::URL_LABEL => "",
                self::MESSAGE_LABEL => '<div>No Message</div>',
        ];
    }

    /** checks params
     * @param mixed $course, $course object or coursetag or null
     * @param mixed $module, $module object or moduletag or null
     * @param mixed $activity, $activity object or activitytag or null
     * @param mixed $entry, entry data of activity (faster process)
     * @return [] [self::STATUS_LABEL=>true/false,'course'=>$course,'module=>$module,'activity'=>$activity]
     */
    private function checkParams($course, $module, $activity, $entry):array
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

        if ($entry) {
            if (!is_array($entry)) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                    self::MESSAGE_LABEL => '$entry should be an array !'];
            }
            if (!isset($entry['id_fiche'])) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                    self::MESSAGE_LABEL => '$entry[\'id_fiche\'] should be defined !'];
            }
            if ($entry['id_fiche'] != $activity->getTag()) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                    self::MESSAGE_LABEL => '$entry[\'id_fiche\'] should be equal to activity\'s tag !'];
            }
        }

        return [
            self::STATUS_LABEL => self::STATUS_CODE_OK,
            'course' => $course ?? null,
            'module' => $module ?? null,
            'activity' => $activity ?? null,
            'entry' => $entry ?? null,
        ];
    }
}
