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
     * @param string|null $userId, id of the concerned learner, if all learners for admin or current learner
     * @param string|null $courseId, id of the concerned course, null = all courses
     * @param string|null $moduleId, id of the concerned module, null = all modules
     * @param string|null $activityId, id of the concerned activity, null = all activities
     * @param string|null $quizId, id of the concerned quiz, null = all quizzes
     * @return array|null [self::STATUS_LABEL=>0(OK)/1(error)/2(no result),
     *      (self::RESULTS_LABEL=>[{"learner"=>"userId", "course"=>"courseId,
     *       "module"=>"moduleId, "activity"=>"activityId, "quizId"=>"quizId",
     *       "log_time"=>...,"result"=>"10"},{"log_time"...}]
     *      ,'message'=>'error message')]
     */
    public function getQuizResults(
        ?string $userId = null,
        ?string $courseId = null,
        ?string $moduleId = null,
        ?string $activityId = null,
        ?string $quizId = null
    ): array {

        /* check params */
        $data = $this->checkParams($userId, $courseId, $moduleId, $activityId, $quizId);
        if ($data[self::STATUS_LABEL] == self::STATUS_CODE_ERROR) {
            return $data;
        } else {
            unset($data[self::STATUS_LABEL]);
        }
        /* find results */
        if (empty($results = $this->findResults($data))) {
            return [self::STATUS_LABEL => self::STATUS_CODE_NO_RESULT,
                self::MESSAGE_LABEL => 'No results'];
        }

        return [self::STATUS_LABEL => self::STATUS_CODE_OK,
            self::RESULTS_LABEL => $results];
    }

    /**
     * method that return an array giving values checked for the selected activity quiz
     * @param string|null $userId, id of the concerned learner
     * @param string|null $courseId, id of the concerned course
     * @param string|null $moduleId, id of the concerned module
     * @param string|null $activityId, id of the concerned activity
     * @param string|null $quizId, id of the concerned quiz
     * @return null ['status'=>false/true,('message'=>'error message',
     *                'course'=>$course,'module'=>$module, 'activity'=>$activity,
     *                'learner'=>$leaner,'quizId'=>$quizId]
     */
    private function checkParams(
        ?string $userId = null,
        ?string $courseId = null,
        ?string $moduleId = null,
        ?string $activityId = null,
        ?string $quizId = null
    ): array {
        if (!empty($courseId) && !$course = $this->courseManager->getCourse($courseId)) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => 'Not existing {courseId}: '.$courseId];
        }
        if (!empty($moduleId)) {
            if (empty($courseId)) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::MESSAGE_LABEL => '{courseId} should be defined when {moduleId} is defined !'];
            } elseif (!$course->hasModule($moduleId)) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{moduleId}: '.$moduleId.' is not a module of the {courseId}: '.$courseId];
            } else {
                if (!$module = $this->courseManager->getModule($moduleId)) {
                    return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => 'Not existing {moduleId}: '.$moduleId];
                }
            }
        }
        if (!empty($activityId)) {
            if (empty($courseId) || empty($moduleId)) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                self::MESSAGE_LABEL => '{courseId} and {moduleId} should be defined when {activityId} is defined !'];
            } elseif (!$module->hasActivity($activityId)) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{activityId}: '.$activityId.' is not an activity of the {moduleId}: '.$moduleId];
            } else {
                if (!$activity = $this->courseManager->getActivity($activityId)) {
                    return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => 'Not existing {activityId}: '.$activityId];
                }
            }
        }

        /* set learner from $userId */
        $currentUser = $this->learnerManager->getLearner(); // current user
        if (!empty($userId)) {
            if (empty($this->userManager->getOneByName($userId)) || !$learner = $this->learnerManager->getLearner($userId)) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{$userId}: '.$userId.'is not existing !'];
            }
            if (!$currentUser->isAdmin() && $currentUser->getUsername() != $learner->getUsername()) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => 'You can not read results for other learner than you !'];
            }
        } elseif (!$currentUser->isAdmin()) {
            $learner = $this->learnerManager->getLearner(); // current user
        }
        
        return [
            self::STATUS_LABEL=>self::STATUS_CODE_OK,
            'learner'=>$learner ?? null,
            'course'=>$course ?? null,
            'module'=>$module ?? null,
            'activity'=>$activity ?? null,
            'quizId'=>$quizId ?? null,
        ];
    }

    /**
     * Method that find the results for a specific user, activity and quizId, null if not existing
     * @param array $data ['course'=>$course,'module'=>$module, 'activity'=>$activity, 'learner'=>$leaner,'quizId'=>$quizId] ; null = all
     * @param bool $withRaw put raw data (for deleting)
     * @return null|array null if no result otherwise [self::STATUS_LABEL=>0(OK)/1(error)/2(no result),
     *      (self::RESULTS_LABEL=>[{"learner"=>"userId", "course"=>"courseId,
     *       "module"=>"moduleId, "activity"=>"activityId, "quizId"=>"quizId",
     *       "log_time"=>...,"result"=>"10"},{"log_time"...}]
     *      ,'message'=>'error message')]
     */
    private function findResults($data, bool $withRaw = false): ?array
    {
        $like = $data['course'] ? '%"course":"' . $data['course']->getTag() . '"%' : '';
        $like .= $data['module'] ? '%"module":"' . $data['module']->getTag() . '"%' : '';
        $like .= $data['activity'] ? '%"activity":"' . $data['activity']->getTag() . '"%' : '';
        $like .= $data['quizId'] ? '%"quizId":"' . $data['quizId'] . '"%' : '';
        $like = empty($like) ? '%' : $like ;
        $results = $this->tripleStore->getMatching(
            $data['learner'] ? $data['learner']->getUsername() : null,
            self::LMS_TRIPLE_PROPERTY_NAME_QUIZ_RESULT,
            $like,
            '=',
            '=',
            'LIKE'
        );
        if (!$results) {
            return null;
        }
        return array_map(function ($result) use ($withRaw) {
            $values = json_decode($result['value'], true);
            return [
                'learner' => $result['resource'],
                'course' => $values['course'],
                'module' => $values['module'],
                'activity' => $values['activity'],
                'quizId' => $values['quizId'],
                'log_time' => $values['log_time'],
                self::RESULT_LABEL => $values[self::RESULT_LABEL]
            ] + ($withRaw ? ['raw' => $result['value']]:[]);
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
        
        /* check params */
        foreach (['courseId','moduleId','activityId','quizId'] as $varName) {
            if (empty($$varName)) {
                return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => '{'.$varName.'} should be defined'];
            }
        }
        $data = $this->checkParams($userId, $courseId, $moduleId, $activityId, $quizId);
        if ($data[self::STATUS_LABEL] == self::STATUS_CODE_ERROR) {
            return $data;
        } else {
            unset($data[self::STATUS_LABEL]);
            $data['learner'] = $data['learner'] ?? $this->learnerManager->getLearner(); // current user
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

    
    /**
     * method that delete results for the selected quiz
     * @param string|null $userId, id of the concerned learner, if all learners for admin
     * @param string|null $courseId, id of the concerned course, null = all courses
     * @param string|null $moduleId, id of the concerned module, null = all modules
     * @param string|null $activityId, id of the concerned activity, null = all activities
     * @param string|null $quizId, id of the concerned quiz, null = all quizzes
     * @return array|null [self::STATUS_LABEL=>0(OK)/1(error)/2(not existing)
     *      (,'message'=>'error message')]
     */
    public function deleteQuizResults(
        ?string $userId = null,
        ?string $courseId = null,
        ?string $moduleId = null,
        ?string $activityId = null,
        ?string $quizId = null
    ): array {

        /* Check if admin */
        if (!$this->learnerManager->getLearner()->isAdmin()) {
            return [self::STATUS_LABEL => self::STATUS_CODE_ERROR, self::MESSAGE_LABEL => 'DELETE only authorized for admins!'];
        }

        /* check params */
        $data = $this->checkParams($userId, $courseId, $moduleId, $activityId, $quizId);
        if ($data[self::STATUS_LABEL] == self::STATUS_CODE_ERROR) {
            return $data;
        } else {
            unset($data[self::STATUS_LABEL]);
        }
        /* find results to delete */
        if (empty($results = $this->findResults($data, true))) {
            return [self::STATUS_LABEL => self::STATUS_CODE_NO_RESULT,
                self::MESSAGE_LABEL => 'No results'];
        }

        if (empty($data['learner'])) {
            /* delete without value : faster */
            $learners = array_unique(array_map(function ($result) {
                return $result['learner'];
            }, $results));
            foreach ($learners as $learner) {
                if ($this->tripleStore->delete(
                    $learner,
                    self::LMS_TRIPLE_PROPERTY_NAME_QUIZ_RESULT,
                    null,
                    '',
                    ''
                ) > 0) {
                    return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                    self::MESSAGE_LABEL => 'Error when deleting quiz\'results for '.$learner];
                }
            }
        } else {
            foreach ($results as $result) {
                if ($this->tripleStore->delete(
                    $result['learner'],
                    self::LMS_TRIPLE_PROPERTY_NAME_QUIZ_RESULT,
                    $result['raw'],
                    '',
                    ''
                ) > 0) {
                    return [self::STATUS_LABEL => self::STATUS_CODE_ERROR,
                    self::MESSAGE_LABEL => 'Error when deleting quiz\'results for '.$learner];
                }
            }
        }
        return [self::STATUS_LABEL => self::STATUS_CODE_OK];
    }

    /** keepOnlyBestResult
     * @param array $results
     * @return array $results
     */
    public function keepOnlyBestResult($results):array
    {
        // create unique key
        $results_with_unique_id = array_map(function ($result) {
            $result['unique_id'] = $result['learner'] . '_' . $result['course'] . '_' . $result['module'] . '_' . $result['activity'] . '_' . $result['quizId'];
            return $result;
        }, $results);

        $results_with_unique_id = array_filter($results_with_unique_id, function ($result) use ($results_with_unique_id) {
            $currentUniqueId = $result['unique_id'];
            $currentResult = $result['result'];
            $higher_results_same_unique_ids = array_filter($results_with_unique_id, function ($previous_result) use ($currentUniqueId, $currentResult) {
                return $previous_result['unique_id'] == $currentUniqueId && $previous_result['result'] > $currentResult;
            });
            return empty($higher_results_same_unique_ids);
        });

        return $results_with_unique_id;
    }
}
