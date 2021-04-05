<?php


namespace YesWiki\Lms\Service;

use YesWiki\Core\Service\TripleStore;
use YesWiki\Wiki;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\LearnerManager;

class QuizManager
{
    protected const LMS_TRIPLE_PROPERTY_NAME_QUIZ_TOKEN =  'https://yeswiki.net/vocabulary/lms-quiz-token' ;
    protected const LMS_TRIPLE_PROPERTY_NAME_QUIZ_RESULT =  'https://yeswiki.net/vocabulary/lms-quiz-results' ;
    public const STATUS_LABEL =  'status' ;
    public const TOKEN_LABEL =  'token' ;
    public const MESSAGE_LABEL =  'message' ;

    protected $tripleStore;
    protected $wiki;
    protected $courseManager;
    protected $learnerManager;

    /**
     * QuizManager constructor
     *
     * @param TripleStore $tripleStore the injected TripleStore instance
     * @param Wiki $wiki
     * @param CourseManager $courseManager
     * @param LearnerManager $learnerManager
     */
    public function __construct(
        TripleStore $tripleStore,
        Wiki $wiki,
        CourseManager $courseManager,
        LearnerManager $learnerManager
    ) {
        $this->tripleStore = $tripleStore;
        $this->wiki = $wiki;
        $this->courseManager = $courseManager;
        $this->learnerManager = $learnerManager;
    }

    /**
     * method that return an array giving token for the selected activity
     * @param string $courseTag, tag of the concerned course
     * @param string $moduleTag, tag of the concerned module
     * @param string $activityTag, tag of the concerned activity
     * @return array ['status'=>true/false,('token'=>'token','message'=>'error message')]
     */
    public function getQuizToken(string $courseTag, string $moduleTag, string $activityTag): array
    {
        /* check params */
        $data = $this->checkParamsForGetQuizToken($courseTag, $moduleTag, $activityTag);
        if (!$data[self::STATUS_LABEL]) {
            return $data;
        } else {
            unset($data[self::STATUS_LABEL]);
        }

        return [self::STATUS_LABEL => false,
            self::MESSAGE_LABEL => 'api not ready'];
    }

    /**
     * method that return an array giving values checked for the selected activity quiz
     * @param string $courseTag, tag of the concerned course
     * @param string $moduleTag, tag of the concerned module
     * @param string $activityTag, tag of the concerned activity
     * @return null ['status'=>false/true,('message'=>'error message',
     *                'course'=>$course,'module'=>$module, 'activity'=>$activity, 'learner'=>$leaner]
     */
    private function checkParamsForGetQuizToken(string $courseTag, string $moduleTag, string $activityTag): array
    {
        if (empty($courseTag)) {
            return [self::STATUS_LABEL => false, self::MESSAGE_LABEL => '{course} shoud not be empty !'];
        }
        if (!$course = $this->courseManager->getCourse($courseTag)) {
            return [self::STATUS_LABEL => false, self::MESSAGE_LABEL => 'Not existing {course} :'.$courseTag];
        }
        if (empty($moduleTag)) {
            return [self::STATUS_LABEL => false, self::MESSAGE_LABEL => '{module} shoud not be empty !'];
        }
        if (!$course->hasModule($moduleTag)) {
            return [self::STATUS_LABEL => false, self::MESSAGE_LABEL => '{module} :'.$moduleTag.' is not a module of the {course} :'.$courseTag];
        } else {
            if (!$module = $this->courseManager->getModule($moduleTag)) {
                return [self::STATUS_LABEL => false, self::MESSAGE_LABEL => 'Not existing {module} :'.$moduleTag];
            }
        }
        if (empty($activityTag)) {
            return [self::STATUS_LABEL => false, self::MESSAGE_LABEL => '{activity} shoud not be empty !'];
        }
        if (!$module->hasActivity($activityTag)) {
            return [self::STATUS_LABEL => false, self::MESSAGE_LABEL => '{activity} :'.$activityTag.' is not an activity of the {module} :'.$moduleTag];
        } else {
            if (!$activity = $this->courseManager->getActivity($activityTag)) {
                return [self::STATUS_LABEL => false, self::MESSAGE_LABEL => 'Not existing {activity} :'.$activityTag];
            }
        }
        if (!$learner = $this->learnerManager->getLearner()) {
            return [self::STATUS_LABEL => false, self::MESSAGE_LABEL => 'You should be connected as learner !'];
        }

        return [
            self::STATUS_LABEL=>true,
            'course'=>$course,
            'module'=>$module,
            'activity'=>$activity,
            'learner'=>$learner,
        ];
    }
}
