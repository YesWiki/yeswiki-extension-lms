<?php

use YesWiki\Core\YesWikiAction;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\DateManager;
use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Lms\Service\QuizManager;

class QuizResultsAction extends YesWikiAction
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
            'course' => $_GET['course'] ?? $args['course'] ?? null ,
            'module' => $_GET['module'] ?? $args['module']  ?? null ,
            'activity' => $_GET['activity'] ?? $args['activity']  ?? null ,
            'quizId' => $_GET['quizId'] ?? $args['quizId']  ?? null ,
            'learner' => $_GET['learner'] ?? $args['learner']  ?? null ,
            'rawdata' => $this->formatBoolean($_GET['rawdata'] ?? $args['rawdata']  ?? null, false) ,
            'onlybest' => $this->formatBoolean($_GET['onlybest'] ?? $args['onlybest']  ?? null, false) ,
            'noadmins' => $this->formatBoolean($_GET['noadmins'] ?? $args['noadmins']  ?? null, false) ,
            'log_time' => $_GET['log_time'] ??  null ,
            'urlParams' => $this->formatArray($_GET['urlParams'] ?? null),
            'quiz_results_mode' => $_GET['quiz_results_mode'] ?? $args['quiz_results_mode']  ?? null ,
            'content' => ($this->wiki->GetMethod() == 'render') ? '{{quizresults}}' : null ,
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

        // reserved only to the admins
        $currentLearner = $this->learnerManager->getLearner();
        if (!$currentLearner || !$currentLearner->isAdmin()) {
            if (empty($this->arguments['calledBy'])) {
                // not called from other action : display message
                return $this->render("@templates/alert-message.twig", [
                    'type' => 'danger',
                    'message' => _t('ACLS_RESERVED_FOR_ADMINS') . ' ('.get_class($this).')'
                ]);
            } else {
                // called from other action : do nothing be quiet
                return null;
            }
        }

        // when called from other action and quiz_results_mode not set :
        // do nothing be quiet to not change the behaviour of the parent action
        if (!empty($this->arguments['calledBy']) && empty($this->arguments['quiz_results_mode'])) {
            return null;
        }

        // special mode for delete
        if ($this->arguments['quiz_results_mode'] == 'delete') {
            $this->delete();
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

            if ($this->arguments['noadmins'] && !$this->params->get('lms_config')['save_progress_for_admins']) {
                $results = array_filter($results, function ($result) {
                    return !($result['learner']->isAdmin());
                });
            }
        } elseif ($this->arguments['noadmins'] && !$this->params->get('lms_config')['save_progress_for_admins']) {
            $results = array_filter($results, function ($result) {
                return !($this->learnerManager->getLearner($result['learner'])->isAdmin());
            });
        }

        $urlParams = [];
        foreach (['course','module','activity','quizId','learner','rawdata','onlybest','noadmins','content'] as $param) {
            if (!empty($this->arguments[$param])) {
                $urlParams[$param] = $this->arguments[$param];
            }
        }

        return $this->render(
            '@lms/quiz-results.twig',
            [
                'results' => $results,
                'rawdata' => $this->arguments['rawdata'] ,
                'urlParams' => $urlParams,
                'handler' => !empty($this->arguments['content']) ? 'render' : null,
                'dateFormat'=>DateManager::DATETIME_FORMAT,
            ]
        );
    }

    /** delete quiz results
     *
     */
    private function delete()
    {
        $this->quizManager->deleteQuizResults(
            $this->arguments['learner'],
            $this->arguments['course'],
            $this->arguments['module'],
            $this->arguments['activity'],
            $this->arguments['quizId'],
            $this->arguments['log_time']
        );
        // reset GET params

        foreach (['course','module','activity','quizId','learner','log_time','content','quiz_results_mode'] as $key) {
            if (!in_array($key, $this->arguments['urlParams'])) {
                $this->arguments[$key] = null;
            }
        }
        foreach (['rawdata','onlybest','noadmins'] as $key) {
            if (!in_array($key, $this->arguments['urlParams'])) {
                $this->arguments[$key] = false;
            }
        }
    }
}
