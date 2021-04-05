<?php

namespace YesWiki\Lms\Controller;

use Symfony\Component\Routing\Annotation\Route;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\YesWikiController;
use YesWiki\Lms\Service\QuizManager;

class ApiController extends YesWikiController
{
    
    /**
     * Get quizz's results for a user, course,module, activity and quizId
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAUserAndAQuizz($userId, $courseId, $moduleId, $activityId, $quizId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->getQuizResultsForAUserAndAQuizz($userId, $courseId, $moduleId, $activityId, $quizId)
        );
    }
    
    /**
     * @Route("/api/lms/{course}/{module}/{activity}/quiz",methods={"POST"},options={"acl":{"public"}})
     *
     * Save quiz results for a learner with a token given by getQuizzToken in $_POST['token']
     * and results in json in $_POST['results']
     */
    public function saveQuizzResult($course, $module, $activity)
    {
        /* check $_POST */
        if (empty($_POST['token'])||empty($_POST['results'])) {
            return new ApiResponse(
                ['status' => false,
                'message' => (empty($_POST['token']) ? 'you must define $_POST[\'token\'], ':'').
                (empty($_POST['results']) ? 'you must define $_POST[\'results\']':'')]
            );
        }

        return new ApiResponse(
            ['status' => false,
            'message' => 'test for quiz '.$activity.'__token :'.$_POST['token'] ]
        );
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
        $output .= '"'.QuizManager::RESULTS_LABEL.'":"23", // value in percent<br />';
        $output .= '"'.QuizManager::MESSAGE_LABEL.'":"error message of needed",]</code><br />';
        $output .= '<b>You must sent cookies to be connected as learner or admin.</b><br />';

        
        $urlSaveQuizzResult = $this->wiki->Href('lms/{course}/{module}/{activity}/quiz', 'api');
        $output .= '<br />The following code :<br />';
        $output .= 'POST <b><code>'.$urlSaveQuizzResult.'</code></b><br />';
        $output .= 'saves data sent in json in $_POST[\'results\'] with token in $_POST[\'token\']<br />';
        $output .= 'Return:<br />';
        $output .= '<code>["status":true/false,<br />';
        $output .= '"message":"error message"]</code><br />';
        return $output;
    }
}
