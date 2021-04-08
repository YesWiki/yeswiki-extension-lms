<?php

use YesWiki\Core\YesWikiAction;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\DateManager;
use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Lms\Service\QuizManager;

class QuizzesResultsAction extends YesWikiAction
{
    protected $learnerManager;
    protected $quizManager;
    protected $courseManager;
    protected $dateManager;
    
    /**
     * format arguments property
     * @param array $args
     * @return array args
     */
    protected function formatArguments($args)
    {
        return [
            'course' => $_REQUEST['course'] ?? $args['course'] ?? null ,
            'module' => $_REQUEST['module'] ?? $args['module']  ?? null ,
            'activity' => $_REQUEST['activity'] ?? $args['activity']  ?? null ,
            'quizId' => $_REQUEST['quizId'] ?? $args['quizId']  ?? null ,
            'learner' => $_REQUEST['learner'] ?? $args['learner']  ?? null ,
            'rawdata' => $this->formatBoolean($_REQUEST['rawdata'] ?? $args['rawdata']  ?? null, false) ,
            'onlybest' => $this->formatBoolean($_REQUEST['onlybest'] ?? $args['onlybest']  ?? null, false) ,
            'quizzes_results_mode' => $this->formatBoolean($_REQUEST, false, 'quizzes_results_mode') ,
        ];
    }
    /**
     * run the controller
     * @return string|null null if nothing to do
     */
    public function run(): ?string
    {
        $this->courseManager = $this->getService(CourseManager::class);
        $this->learnerManager = $this->getService(LearnerManager::class);
        $this->quizManager = $this->getService(QuizManager::class);
        $this->dateManager = $this->getService(DateManager::class);

        $currentLearner = $this->learnerManager->getLearner();
        if (!$currentLearner || !$currentLearner->isAdmin()) {
            if (empty($this->arguments['calledBy'])) {
                // reserved only to the admins
                return $this->render("@templates/alert-message.twig", [
                    'type' => 'danger',
                    'message' => _t('ACLS_RESERVED_FOR_ADMINS') . ' ('.get_class($this).')'
                ]);
            } else {
                return null;
            }
        }

        if (!empty($this->arguments['calledBy']) && !$this->arguments['quizzes_results_mode']) {
            return null;
        }

        $rawResults = $this->quizManager->getQuizResults(
            $this->arguments['learner'],
            $this->arguments['course'],
            $this->arguments['module'],
            $this->arguments['activity'],
            $this->arguments['quizId'],
        );
        if (!isset($rawResults[QuizManager::STATUS_LABEL])) {
            throw new Exception('QuizManager::getQuizResults() returns array without key \''.QuizManager::STATUS_LABEL.'\' !');
        }
        switch ($rawResults[QuizManager::STATUS_LABEL]) {
            case QuizManager::STATUS_CODE_ERROR:
                throw new Exception('QuizManager::getQuizResults() returns an error : \''.
                    $rawResults[QuizManager::MESSAGE_LABEL].'\'!');
                break;
            case QuizManager::STATUS_CODE_NO_RESULT:
                $results = [] ;
                break;
            case QuizManager::STATUS_CODE_OK:
                $results = $rawResults[QuizManager::RESULTS_LABEL] ;
                break;
            default:
                throw new Exception('QuizManager::getQuizResults() returns an unknown status code : \''.
                    $rawResults[QuizManager::STATUS_LABEL].'\'!');
                break;
        }

        if ($this->arguments['onlybest']) {
            $results = $this->quizManager->keepOnlyBestResult($results);
        }
        if (!$this->arguments['rawdata']) {
            // put objects in results
            $coursesCached = [];
            $modulesCached = [];
            $activitiesCached = [];
            $learnersCached = [];
            $results = array_map(function ($result) use ($coursesCached, $modulesCached, $activitiesCached, $learnersCached) {
                if (!isset($coursesCached[$result['course']])) {
                    $coursesCached[$result['course']] = $this->courseManager->getCourse($result['course']);
                }
                $result['course'] = $coursesCached[$result['course']];
                if (!isset($modulesCached[$result['module']])) {
                    $modulesCached[$result['module']] = $this->courseManager->getModule($result['module']);
                }
                $result['module'] = $modulesCached[$result['module']];
                if (!isset($activitiesCached[$result['activity']])) {
                    $activitiesCached[$result['activity']] = $this->courseManager->getActivity($result['activity']);
                }
                $result['activity'] = $activitiesCached[$result['activity']];
                if (!isset($learnersCached[$result['learner']])) {
                    $learnersCached[$result['learner']] = $this->learnerManager->getLearner($result['learner']);
                }
                $result['learner'] = $learnersCached[$result['learner']];
                $result['log_time'] = $this->dateManager->createDatetimeFromString($result['log_time']);
                return $result;
            }, $results);
        }

        return $this->render(
            '@lms/quizzes-results.twig',
            [
                'results' => $results,
                'rawdata' => $this->arguments['rawdata'] ,
            ]
        );
    }
}
