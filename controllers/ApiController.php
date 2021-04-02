<?php

namespace YesWiki\Lms\Controller;

use Symfony\Component\Routing\Annotation\Route;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\YesWikiController;
use YesWiki\Lms\Service\QuizManager;
use YesWiki\Lms\Service\ActivityConditionsManager;
use YesWiki\Lms\Service\CourseManager; // TODO to move to ActivityConditionsManager

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
                ->getQuizResults($userId, $courseId, $moduleId, $activityId, $quizId)
        );
    }

    /**
     * Get quizzes' results for a user, course, module, activity
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAUserAndAnActivity($userId, $courseId, $moduleId, $activityId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->getQuizResults($userId, $courseId, $moduleId, $activityId, null)
        );
    }
    
    /**
     * Get quizzes' results for a user, course, module
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAUserAndAModule($userId, $courseId, $moduleId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->getQuizResults($userId, $courseId, $moduleId, null, null)
        );
    }
    
    /**
     * Get quizzes' results for a user, course
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAUserAndACourse($userId, $courseId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->getQuizResults($userId, $courseId, null, null, null)
        );
    }
    
    /**
     * Get quizzes' results for a user, course
     * @Route("/api/lms/users/{userId}/quizresults",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAUser($userId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->getQuizResults($userId, $_GET['course'] ?? null, $_GET['module'] ??  null, $_GET['activity'] ?? null, null)
        );
    }

    /**
     * Get quiz's results for a course, module, activity and quizId (all users for admins otherwise only for current user)
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAQuiz($courseId, $moduleId, $activityId, $quizId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->getQuizResults(null, $courseId, $moduleId, $activityId, $quizId)
        );
    }
    
    /**
     * Get quiz's results for a course, module, activity (all users for admins otherwise only for current user)
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}/{activityId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAnActivity($courseId, $moduleId, $activityId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->getQuizResults(null, $courseId, $moduleId, $activityId, null)
        );
    }
    
    /**
     * Get quiz's results for a course, module (all users for admins otherwise only for current user)
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAModule($courseId, $moduleId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->getQuizResults(null, $courseId, $moduleId, null, null)
        );
    }

    /**
     * Get quiz's results for a course (all users for admins otherwise only for current user)
     * @Route("/api/lms/quizresults/{courseId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForACourse($courseId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->getQuizResults(null, $courseId, null, null, null)
        );
    }

    /**
     * Get quiz's results  (all users for admins otherwise only for current user)
     * @Route("/api/lms/quizresults",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResults()
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->getQuizResults(null, $_GET['course'] ?? null, $_GET['module'] ??  null, $_GET['activity'] ?? null, null)
        );
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
     * save quiz's result for a user, course, module, activity and quizId
     * @Route("/api/lms/users/{userId}/quizresults/{quizId}",methods={"POST"},options={"acl":{"public","+"}})
     *
     * Save quiz result for a learner with result as percent in float in $_POST['result']
     */
    public function saveQuizResultForAUserAndAQuizByPost($userId, $quizId)
    {
        /* check $_POST */
        if (empty($_POST[QuizManager::RESULT_LABEL])) {
            return new ApiResponse(
                [QuizManager::STATUS_LABEL => QuizManager::STATUS_CODE_ERROR,
                QuizManager::MESSAGE_LABEL => 'you must define $_POST[\''.QuizManager::RESULT_LABEL.'\']']
            );
        }
        if (empty($_POST['course'])) {
            return new ApiResponse(
                [QuizManager::STATUS_LABEL => QuizManager::STATUS_CODE_ERROR,
                QuizManager::MESSAGE_LABEL => 'you must define $_POST[\'course\']']
            );
        }
        
        if (empty($_POST['module'])) {
            return new ApiResponse(
                [QuizManager::STATUS_LABEL => QuizManager::STATUS_CODE_ERROR,
                QuizManager::MESSAGE_LABEL => 'you must define $_POST[\'module\']']
            );
        }
        
        if (empty($_POST['activity'])) {
            return new ApiResponse(
                [QuizManager::STATUS_LABEL => QuizManager::STATUS_CODE_ERROR,
                QuizManager::MESSAGE_LABEL => 'you must define $_POST[\'activity\']']
            );
        }

        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->saveQuizResultForAUserAndAQuiz($userId, $_POST['course'], $_POST['module'], $_POST['activity'], $quizId, floatval($_POST[QuizManager::RESULT_LABEL]))
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
     * delete quiz's result for a user and for course, module, activity and quizId
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAUserAndAQuiz($userId, $courseId, $moduleId, $activityId, $quizId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->deleteQuizResults($userId, $courseId, $moduleId, $activityId, $quizId)
        );
    }

    /**
     * delete quiz's result for a user and for course, module, activity
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAUserAndAnActivity($userId, $courseId, $moduleId, $activityId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->deleteQuizResults($userId, $courseId, $moduleId, $activityId, null)
        );
    }
    /**
     * delete quiz's result for a user and for course, module
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAUserAndAModule($userId, $courseId, $moduleId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->deleteQuizResults($userId, $courseId, $moduleId, null, null)
        );
    }
    /**
     * delete quiz's result for a user and for course
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAUserAndACourse($userId, $courseId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->deleteQuizResults($userId, $courseId, null, null, null)
        );
    }
    /**
     * delete quiz's result for a user
     * @Route("/api/lms/users/{userId}/quizresults",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAUser($userId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->deleteQuizResults($userId, null, null, null, null)
        );
    }

    /**
     * delete quiz's result for all users and for course, module, activity and quizId
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAQuiz($courseId, $moduleId, $activityId, $quizId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->deleteQuizResults(null, $courseId, $moduleId, $activityId, $quizId)
        );
    }

    /**
     * delete quiz's result for all users and for course, module, activity
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}/{activityId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAnActivity($courseId, $moduleId, $activityId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->deleteQuizResults(null, $courseId, $moduleId, $activityId, null)
        );
    }

    /**
     * delete quiz's result for all users and for course, module
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAModule($courseId, $moduleId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->deleteQuizResults(null, $courseId, $moduleId, null, null)
        );
    }

    /**
     * delete quiz's result for all users and for course
     * @Route("/api/lms/quizresults/{courseId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForACourse($courseId)
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->deleteQuizResults(null, $courseId, null, null, null)
        );
    }

    /**
     * delete quiz's result for all users
     * @Route("/api/lms/quizresults",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResults()
    {
        return new ApiResponse(
            $this->getService(QuizManager::class)
                ->deleteQuizResults(null, null, null, null, null)
        );
    }

    /**
     * @Route("/api/lms/check-conditions/{course}/{module}/{activity}")
     */
    public function checkConditions($course, $module, $activity)
    {
        $courseManager = $this->getService(CourseManager::class);
        return new ApiResponse(
            $this->getService(ActivityConditionsManager::class)
                ->checkActivityConditions(
                    $courseManager->getCourse($course),
                    $courseManager->getCourse($module),
                    $courseManager->getCourse($activity),
                )
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

        $output .= 'The following codes give quiz\'s results:<br />';
        $output .= 'GET <a href="';
        $output .= $this->wiki->Href('lms/users/TestUser/quizresults/test-course/test-module/test-activity/test-id', 'api');
        $output .= '"><code>';
        $output .= $this->wiki->Href('lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}', 'api');
        $output .= '</code></a> for a quiz<br />';
        $output .= 'GET <a href="';
        $output .= $this->wiki->Href('lms/users/TestUser/quizresults/test-course/test-module/test-activity', 'api');
        $output .= '"><code>';
        $output .= $this->wiki->Href('lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}', 'api');
        $output .= '</code></a> for all quizzes of an activity<br />';
        $output .= 'GET <a href="';
        $output .= $this->wiki->Href('lms/users/TestUser/quizresults/test-course/test-module', 'api');
        $output .= '"><code>';
        $output .= $this->wiki->Href('lms/users/{userId}/quizresults/{courseId}/{moduleId}', 'api');
        $output .= '</code></a> for all quizzes of all activities of a module<br />';
        $output .= 'GET <a href="';
        $output .= $this->wiki->Href('lms/users/TestUser/quizresults/test-course', 'api');
        $output .= '"><code>';
        $output .= $this->wiki->Href('lms/users/{userId}/quizresults/{courseId}', 'api');
        $output .= '</code></a> for all quizzes of all modules of a course<br />';
        $output .= 'GET <a href="';
        $output .= $this->wiki->Href('lms/users/TestUser/quizresults', 'api');
        $output .= '"><code>';
        $output .= $this->wiki->Href('lms/users/{userId}/quizresults', 'api');
        $output .= '</code></a> for all quizzes for the user<br />';
        $output .= '<br /><b>All routes can be used without \'/users/{userId}/\' to get all quizzes for all users<br>';
        $output .= 'if connected as admin (otherwise current user)</b>. Example:<br />';
        $output .= 'GET <a href="';
        $output .= $this->wiki->Href('lms/quizresults/test-course/test-module/test-activity', 'api');
        $output .= '"><code>';
        $output .= $this->wiki->Href('lms/quizresults/{courseId}/{moduleId}/{activityId}', 'api');
        $output .= '</code></a> for all quizzes of an activity for all users<br />';
        $output .= '<br />Results in json<br />';
        $output .= 'Error:<br />';
        $output .= '<code>["'.QuizManager::STATUS_LABEL.'":'.QuizManager::STATUS_CODE_ERROR.',';
        $output .= '"'.QuizManager::MESSAGE_LABEL.'":"error message if needed"]</code><br />';
        $output .= 'Success:<br />';
        $output .= '<code>["'.QuizManager::STATUS_LABEL.'":'.QuizManager::STATUS_CODE_OK.',';
        $output .= '"'.QuizManager::RESULTS_LABEL.'":[{...},{...},<br>';
        $output .= '{"learner":"userId","course":"courseId","module":"moduleId","activity":"activityId",<br>';
        $output .= '"quizId":"quizId","log_time":"2021-01-01 01:23:22","'.QuizManager::RESULT_LABEL.'":"32"}]]// value in percent</code><br />';
        $output .= 'No results:<br />';
        $output .= '<code>["'.QuizManager::STATUS_LABEL.'":'.QuizManager::STATUS_CODE_NO_RESULT.']</code><br />';
        $output .= '<b>You must sent cookies to be connected as learner or admin.</b><br />';

        $output .= '<br />GET <a href="';
        $output .= $this->wiki->Href('lms/quizresults/test-course/test-module/test-activity/test-id', 'api');
        $output .= '"><code>';
        $output .= $this->wiki->Href('lms/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}', 'api');
        $output .= '</code></a><br />';
        $output .= 'Same as previous but for current connected learner<br />';
        
        $urlSaveQuizResult = $this->wiki->Href('lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}', 'api');
        $output .= '<br />The following code :<br />';
        $output .= 'POST <b><code>'.$urlSaveQuizResult.'</code></b><br />';
        $output .= 'saves data float value sent in $_POST[\'result\'] for the specified user<br />';
        $output .= 'Return:<br />';
        $output .= '<code>["'.QuizManager::STATUS_LABEL.'":'.QuizManager::STATUS_CODE_OK.' = OK/';
        $output .= QuizManager::STATUS_CODE_ERROR.' = error,<br />';
        $output .= '"message":"error message"]</code><br />';
        $output .= '<b>You must sent cookies to be connected as learner or admin.</b><br />';
        
        $output .= '<br />POST <b><code>';
        $output .= $this->wiki->Href('lms/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}', 'api');
        $output .= '</code></b><br />';
        $output .= 'Same as previous but for current connected learner<br />';

        
        $output .= '<br />The following codes delete quiz\'s results:<br />';
        $output .= 'DELETE <code>';
        $output .= $this->wiki->Href('lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}', 'api');
        $output .= '</code> for a quiz<br />';
        $output .= 'DELETE <code>';
        $output .= $this->wiki->Href('lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}', 'api');
        $output .= '</code> for all quizzes of an activity<br />';
        $output .= 'DELETE <code>';
        $output .= $this->wiki->Href('lms/users/{userId}/quizresults/{courseId}/{moduleId}', 'api');
        $output .= '</code> for all quizzes of a module<br />';
        $output .= 'DELETE <code>';
        $output .= $this->wiki->Href('lms/users/{userId}/quizresults/{courseId}', 'api');
        $output .= '</code> for all quizzes of a course<br />';
        $output .= 'DELETE <code>';
        $output .= $this->wiki->Href('lms/users/{userId}/quizresults', 'api');
        $output .= '</code> for all quizzes of a user<br />';
        $output .= 'DELETE <code>';
        $output .= $this->wiki->Href('lms/quizresults', 'api');
        $output .= '</code> for all quizzes of all user<br />';
        $output .= '<b>You must sent cookies to be connected as admin.</b><br />';

        $urlCheckConditions = $this->wiki->Href('', 'api/lms/check-conditions/{course}/{module}/{activity}');
        $output .= '<br />The following code :<br />';
        $output .= 'GET <code>'.$urlCheckConditions.'</code><br />';
        $output .= 'gives for {activity} of {module} of {course} for the current user :<br />';
        $output .= '<code>['.ActivityConditionsManager::STATUS_LABEL.':true/false,<br />';
        $output .= ActivityConditionsManager::URL_LABEL.':null|"https://...",<br />';
        $output .= ActivityConditionsManager::MESSAGE_LABEL.':"html code"]</code><br />';
        return $output;
    }
}
