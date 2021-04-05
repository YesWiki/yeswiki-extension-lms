<?php


namespace YesWiki\Lms\Service;

use YesWiki\Core\Service\UserManager;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Wiki;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\LearnerManager;

class QuizManager
{
    protected const LMS_TRIPLE_PROPERTY_NAME_QUIZ_RESULT =  'https://yeswiki.net/vocabulary/lms-quiz-results' ;
    public const STATUS_LABEL =  'status' ;
    public const RESULTS_LABEL =  'results' ;
    public const MESSAGE_LABEL =  'message' ;
    public const STATUS_CODE_OK =  0 ;
    public const STATUS_CODE_ERROR =  1 ;
    public const STATUS_CODE_NO_RESULT =  2 ;

    protected $tripleStore;
    protected $wiki;
    protected $courseManager;
    protected $learnerManager;
    protected $userManager;

    /**
     * QuizManager constructor
     *
     * @param TripleStore $tripleStore the injected TripleStore instance
     * @param Wiki $wiki
     * @param CourseManager $courseManager
     * @param LearnerManager $learnerManager
     * @param UserManager $userManager
     */
    public function __construct(
        TripleStore $tripleStore,
        Wiki $wiki,
        CourseManager $courseManager,
        LearnerManager $learnerManager,
        UserManager $userManager
    ) {
        $this->tripleStore = $tripleStore;
        $this->wiki = $wiki;
        $this->courseManager = $courseManager;
        $this->learnerManager = $learnerManager;
        $this->userManager = $userManager;
    }

    /**
     * method that return an array giving results for the selected quiz
     * @param string $userId, id of the concerned learner
     * @param string $courseId, id of the concerned course
     * @param string $moduleId, id of the concerned module
     * @param string $activityId, id of the concerned activity
     * @param string $quizId, id of the concerned quiz
     * @return array [self::STATUS_LABEL=>0(OK)/1(error)/2(no result),
     *      (RESULTS_LABEL=>float in %,'message'=>'error message')]
     */
    public function getQuizResultsForAUserAndAQuizz(
        string $userId,
        string $courseId,
        string $moduleId,
        string $activityId,
        string $quizId
    ): array {
        /* check params */
        $data = $this->checkParamsForgetQuizResultsForAUserAndAQuizz(
            $userId,
            $courseId,
            $moduleId,
            $activityId,
            $quizId
        );
        if ($data[self::STATUS_LABEL] == self::STATUS_CODE_ERROR) {
            return $data;
        } else {
            unset($data[self::STATUS_LABEL]);
        }

        return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
            self::MESSAGE_LABEL => 'api not ready'];
    }

    /**
     * method that return an array giving values checked for the selected activity quiz
     * @param string $userId, id of the concerned learner
     * @param string $courseId, id of the concerned course
     * @param string $moduleId, id of the concerned module
     * @param string $activityId, id of the concerned activity
     * @param string $quizId, id of the concerned quiz
     * @return null ['status'=>false/true,('message'=>'error message',
     *                'course'=>$course,'module'=>$module, 'activity'=>$activity, 'learner'=>$leaner,'quizzId'=>$quizzId]
     */
    private function checkParamsForgetQuizResultsForAUserAndAQuizz(
        string $userId,
        string $courseId,
        string $moduleId,
        string $activityId,
        string $quizId
    ): array {
        if (empty($courseId)) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{courseId} shoud not be empty !'];
        }
        if (!$course = $this->courseManager->getCourse($courseId)) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => 'Not existing {courseId}: '.$courseId];
        }
        if (empty($moduleId)) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{moduleId} shoud not be empty !'];
        }
        if (!$course->hasModule($moduleId)) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{moduleId}: '.$moduleId.' is not a module of the {courseId}: '.$courseId];
        } else {
            if (!$module = $this->courseManager->getModule($moduleId)) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => 'Not existing {moduleId}: '.$moduleId];
            }
        }
        if (empty($activityId)) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{activityId} shoud not be empty !'];
        }
        if (!$module->hasActivity($activityId)) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{activityId}: '.$activityId.' is not an activity of the {moduleId}: '.$moduleId];
        } else {
            if (!$activity = $this->courseManager->getActivity($activityId)) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => 'Not existing {activityId}: '.$activityId];
            }
        }
        if (empty($userId)) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{userId} shoud not be empty !'];
        }
        if (empty($this->userManager->getOneByName($userId)) || !$learner = $this->learnerManager->getLearner($userId)) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{$userId}: '.$userId.'is not existing !'];
        }
        $currentUser = $this->learnerManager->getLearner(); // current learner
        if (!$currentUser->isAdmin() && $currentUser->getUsername() != $learner->getUsername()) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => 'You can not read results for other learner than you !'];
        }
        if (empty($quizId)) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{quizId} shoud not be empty !'];
        }

        return [
            self::STATUS_LABEL=>self::STATUS_CODE_OK,
            'learner'=>$learner,
            'course'=>$course,
            'module'=>$module,
            'activity'=>$activity,
            'quizId'=>$quizId,
        ];
    }
}
