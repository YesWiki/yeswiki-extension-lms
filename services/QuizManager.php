<?php


namespace YesWiki\Lms\Service;

use Carbon\Carbon;
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
    public const RESULT_LABEL =  'result' ;
    public const MESSAGE_LABEL =  'message' ;
    public const STATUS_CODE_OK =  0 ;
    public const STATUS_CODE_ERROR =  1 ;
    public const STATUS_CODE_NO_RESULT =  2 ;

    protected $tripleStore;
    protected $wiki;
    protected $courseManager;
    protected $dateManager;
    protected $learnerManager;
    protected $userManager;

    /**
     * QuizManager constructor
     *
     * @param TripleStore $tripleStore the injected TripleStore instance
     * @param Wiki $wiki
     * @param CourseManager $courseManager
     * @param DateManager $dateManager
     * @param LearnerManager $learnerManager
     * @param UserManager $userManager
     */
    public function __construct(
        TripleStore $tripleStore,
        Wiki $wiki,
        CourseManager $courseManager,
        LearnerManager $learnerManager,
        UserManager $userManager,
        DateManager $dateManager
    ) {
        $this->tripleStore = $tripleStore;
        $this->wiki = $wiki;
        $this->courseManager = $courseManager;
        $this->learnerManager = $learnerManager;
        $this->userManager = $userManager;
        $this->dateManager = $dateManager;
    }

    /**
     * method that return an array giving results for the selected quiz
     * @param string|null $userId, id of the concerned learner, if null current learner
     * @param string $courseId, id of the concerned course
     * @param string $moduleId, id of the concerned module
     * @param string $activityId, id of the concerned activity
     * @param string $quizId, id of the concerned quiz
     * @return array [self::STATUS_LABEL=>0(OK)/1(error)/2(no result),
     *      (RESULTS_LABEL=>float in %,'message'=>'error message')]
     */
    public function getQuizResultsForAUserAndAQuiz(
        ?string $userId = null,
        string $courseId,
        string $moduleId,
        string $activityId,
        string $quizId
    ): array {
        if (is_null($userId)) {
            // get current user
            $userId = $this->learnerManager->getLearner()->getUsername();
        }
        /* check params */
        $data = $this->checkParams($userId, $courseId, $moduleId, $activityId, $quizId);
        if ($data[self::STATUS_LABEL] == self::STATUS_CODE_ERROR) {
            return $data;
        } else {
            unset($data[self::STATUS_LABEL]);
        }
        /* find results */
        if (empty($results = $this->findResultsForALearnerAnActivityAndAQuiz($data))) {
            return [self::STATUS_LABEL => self::STATUS_CODE_NO_RESULT,
                self::MESSAGE_LABEL => 'No results'];
        }

        return [self::STATUS_LABEL => self::STATUS_CODE_OK,
            self::RESULTS_LABEL => $results];
    }

    /**
     * method that return an array giving values checked for the selected activity quiz
     * @param string $userId, id of the concerned learner
     * @param string $courseId, id of the concerned course
     * @param string $moduleId, id of the concerned module
     * @param string $activityId, id of the concerned activity
     * @param string $quizId, id of the concerned quiz
     * @return null ['status'=>false/true,('message'=>'error message',
     *                'course'=>$course,'module'=>$module, 'activity'=>$activity,
     *                'learner'=>$leaner,'quizId'=>$quizId]
     */
    private function checkParams(
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

    /**
     * Method that find the results for a specific user, activity and quizId, null if not existing
     * @param array $data ['course'=>$course,'module'=>$module, 'activity'=>$activity, 'learner'=>$leaner,'quizId'=>$quizId]
     * @return null|array null if no result then [{"log_time"=>...,"result"=>"10"},{"log_time"...}]
     */
    private function findResultsForALearnerAnActivityAndAQuiz($data): ?array
    {
        $like = '%"course":"' . $data['course']->getTag() . '"%';
        $like .= '%"module":"' . $data['module']->getTag() . '"%';
        $like .= '%"activity":"' . $data['activity']->getTag() . '"%';
        $like .= '%"quizId":"' . $data['quizId'] . '"%';
        $results = $this->tripleStore->getMatching(
            $data['learner']->getUsername(),
            self::LMS_TRIPLE_PROPERTY_NAME_QUIZ_RESULT,
            $like,
            '=',
            '=',
            'LIKE'
        );
        if (!$results) {
            return null;
        }
        return array_map(function ($result) {
            $values = json_decode($result['value'], true);
            return ['log_time' => $values['log_time'],
                self::RESULT_LABEL => $values[self::RESULT_LABEL] ?? 0];
        }, $results);
    }

    /**
     * method that saves result for the selected quiz
     * @param string|null $userId, id of the concerned learner, null if it is the current user
     * @param string $courseId, id of the concerned course
     * @param string $moduleId, id of the concerned module
     * @param string $activityId, id of the concerned activity
     * @param string $quizId, id of the concerned quiz
     * @param float $results, results in percent
     * @return array [self::STATUS_LABEL=>0(OK)/1(error),
     *      ('message'=>'error message')]
     */
    public function saveQuizResultForAUserAndAQuiz(
        ?string $userId = null,
        string $courseId,
        string $moduleId,
        string $activityId,
        string $quizId,
        float $result
    ): array {
        if (is_null($userId)) {
            // get current user
            $userId = $this->learnerManager->getLearner()->getUsername();
        }
        
        /* check params */
        $data = $this->checkParams($userId, $courseId, $moduleId, $activityId, $quizId);
        if ($data[self::STATUS_LABEL] == self::STATUS_CODE_ERROR) {
            return $data;
        } else {
            unset($data[self::STATUS_LABEL]);
        }

        /* save result */
        $codeStatus = $this->tripleStore->create(
            $data['learner']->getUsername(),
            self::LMS_TRIPLE_PROPERTY_NAME_QUIZ_RESULT,
            json_encode(['course' => $data['course']->getTag(),
                         'module' => $data['module']->getTag(),
                         'activity' => $data['activity']->getTag(),
                         'quizId' => $data['quizId'],
                         'log_time' => $this->dateManager->formatDatetime(Carbon::now()),
                         self::RESULT_LABEL => $result,
                        ]),
            '',
            ''
        );

        switch ($codeStatus) {
            case 0:
                return [self::STATUS_LABEL => self::STATUS_CODE_OK];
                break;
            case 3:
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                    self::MESSAGE_LABEL => 'Error when saving results: '.$results.'% for quiz \''
                        .$data['quizId'].'\' in activity \''.$data['activity']->getTag()
                        .'\' for user: '.$data['learner']->getUsername().', triple already existing!'];
            case 1:
            default:
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                    self::MESSAGE_LABEL => 'Error when saving results: '.$results.'% for quiz \''
                        .$data['quizId'].'\' in activity \''.$data['activity']->getTag()
                        .'\' for user: '.$data['learner']->getUsername().'!'];
                break;
        }
    }
}
