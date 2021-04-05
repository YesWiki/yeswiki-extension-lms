<?php

namespace YesWiki\Lms\Controller;

use Symfony\Component\Routing\Annotation\Route;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\YesWikiController;
use YesWiki\Lms\Service\QuizManager;

class ApiController extends YesWikiController
{
    
    /**
     * Get quiz's results for a user, course, module, activity and quizId
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAUserAndAQuiz($userId, $courseId, $moduleId, $activityId, $quizId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->getQuizResultsForAUserAndAQuiz($userId, $courseId, $moduleId, $activityId, $quizId)
        );
    }

    /**
     * Get quiz's results for a user, course, module, activity and quizId
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAQuizz($courseId, $moduleId, $activityId, $quizId)
    {
        return $this->getQuizResultsForAUserAndAQuiz(null, $courseId, $moduleId, $activityId, $quizId);
    }
    
    /**
     * save quiz's result for a user, course, module, activity and quizId
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}",methods={"POST"},options={"acl":{"public","+"}})
     *
     * Save quiz result for a learner with result as percent in float in $_POST['result']
     */
    public function saveQuizResultForAUserAndAQuiz($userId, $courseId, $moduleId, $activityId, $quizId)
    {
        /* check $_POST */
        if (empty($_POST[QuizManager::RESULT_LABEL])) {
            return new ApiResponse(
                [QuizManager::STATUS_LABEL => QuizManager::STATUS_CODE_ERROR,
                QuizManager::MESSAGE_LABEL => 'you must define $_POST[\''.QuizManager::RESULT_LABEL.'\']']
            );
        }

        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->saveQuizResultForAUserAndAQuiz($userId, $courseId, $moduleId, $activityId, $quizId, floatval($_POST['result']))
        );
    }
    
    /**
     * save quiz's result for the connected user and for course, module, activity and quizId
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}",methods={"POST"},options={"acl":{"public","+"}})
     *
     * Save quiz result for a learner with result as percent in float in $_POST['result']
     */
    public function saveQuizResultForAQuiz($courseId, $moduleId, $activityId, $quizId)
    {
        return $this->saveQuizResultForAUserAndAQuiz(null, $courseId, $moduleId, $activityId, $quizId);
    }

    /**
     * Display lms api documentation
     *
     * @return string
     */
    public function getDocumentation()
    {
        $output = '<h2>Extension LMS</h2>';

        $urlGetQuizResultsForAUserAndAQuizz = $this->wiki->Href('lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}', 'api');
        $fullUrlGetQuizResultsForAUserAndAQuizz = $this->wiki->Href('lms/users/TestUser/quizresults/test-course/test-module/test-activity/test-id', 'api');

        $output .= 'The following code :<br />';
        $output .= 'GET <a href="'. $fullUrlGetQuizResultsForAUserAndAQuizz .'"><code>'.$urlGetQuizResultsForAUserAndAQuizz.'</code></a><br />';
        $output .= 'gives a json with token results for the {quizId} quiz of activity {activityId} and for user {userId}<br />';
        $output .= '<code>["'.QuizManager::STATUS_LABEL.'":'.QuizManager::STATUS_CODE_OK.' = OK/';
        $output .= QuizManager::STATUS_CODE_ERROR.' = error/'.QuizManager::STATUS_CODE_NO_RESULT.' = no results,<br />';
        $output .= '"'.QuizManager::RESULTS_LABEL.'":"[{"log_time":"2021-01-01 01:23:22","'.QuizManager::RESULT_LABEL.'":"23"}, // value in percent<br />';
        $output .= '{"log_time":"2021-01-01 01:24:22","'.QuizManager::RESULT_LABEL.'":"32"}]", // value in percent<br />';
        $output .= '"'.QuizManager::MESSAGE_LABEL.'":"error message of needed",]</code><br />';
        $output .= '<b>You must sent cookies to be connected as learner or admin.</b><br />';

        $output .= '<br />GET <a href="';
        $output .= $this->wiki->Href('lms/quizresults/test-course/test-module/test-activity/test-id', 'api');
        $output .= '"><code>';
        $output .= $this->wiki->Href('lms/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}', 'api');
        $output .= '</code></a><br />';
        $output .= 'Same as previous but for current connected leaner<br />';
        
        $urlSaveQuizzResult = $this->wiki->Href('lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}', 'api');
        $output .= '<br />The following code :<br />';
        $output .= 'POST <b><code>'.$urlSaveQuizzResult.'</code></b><br />';
        $output .= 'saves data float value sent in $_POST[\'result\'] for the specified user<br />';
        $output .= 'Return:<br />';
        $output .= '<code>["'.QuizManager::STATUS_LABEL.'":'.QuizManager::STATUS_CODE_OK.' = OK/';
        $output .= QuizManager::STATUS_CODE_ERROR.' = error,<br />';
        $output .= '"message":"error message"]</code><br />';
        $output .= '<b>You must sent cookies to be connected as learner or admin.</b><br />';
        
        $output .= '<br />POST <b><code>';
        $output .= $this->wiki->Href('lms/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}', 'api');
        $output .= '</code></b><br />';
        $output .= 'Same as previous but for current connected leaner<br />';
        return $output;
    }
}
