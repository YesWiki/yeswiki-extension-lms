<?php

namespace YesWiki\Lms\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\Service\ReactionManager;
use YesWiki\Core\YesWikiController;
use YesWiki\Lms\Service\QuizManager;
use YesWiki\Lms\Service\ConditionsChecker;

class ApiController extends YesWikiController
{
    /**
     * Get quiz's results for a user, course, module, activity and quizId
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAUserAndAQuiz($userId, $courseId, $moduleId, $activityId, $quizId)
    {
        $result = $this->getService(QuizManager::class)
                ->getQuizResults($userId, $courseId, $moduleId, $activityId, $quizId);
        $code = ($result[QuizManager::STATUS_LABEL] == QuizManager::STATUS_CODE_OK)
        ? 200 // OK
        : (
            ($result[QuizManager::STATUS_LABEL] == QuizManager::STATUS_CODE_ERROR)
            ? 500 // server error
            : 400 // no result
        );
        return new ApiResponse(['code' => $code]+$result, $code);
    }

    /**
     * Get quizzes' results for a user, course, module, activity
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAUserAndAnActivity($userId, $courseId, $moduleId, $activityId)
    {
        return $this->getQuizResultsForAUserAndAQuiz($userId, $courseId, $moduleId, $activityId, null);
    }

    /**
     * Get quizzes' results for a user, course, module
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAUserAndAModule($userId, $courseId, $moduleId)
    {
        return $this->getQuizResultsForAUserAndAQuiz($userId, $courseId, $moduleId, null, null);
    }

    /**
     * Get quizzes' results for a user, course
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAUserAndACourse($userId, $courseId)
    {
        return $this->getQuizResultsForAUserAndAQuiz($userId, $courseId, null, null, null);
    }

    /**
     * Get quizzes' results for a user, course
     * @Route("/api/lms/users/{userId}/quizresults",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAUser($userId)
    {
        return $this->getQuizResultsForAUserAndAQuiz(
            $userId,
            $_GET['course'] ?? null,
            $_GET['module'] ??  null,
            $_GET['activity'] ?? null,
            null
        );
    }

    /**
     * Get quiz's results for a course, module, activity and quizId (all users for admins otherwise only for current user)
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAQuiz($courseId, $moduleId, $activityId, $quizId)
    {
        return $this->getQuizResultsForAUserAndAQuiz(null, $courseId, $moduleId, $activityId, $quizId);
    }

    /**
     * Get quiz's results for a course, module, activity (all users for admins otherwise only for current user)
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}/{activityId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAnActivity($courseId, $moduleId, $activityId)
    {
        return $this->getQuizResultsForAUserAndAQuiz(null, $courseId, $moduleId, $activityId, null);
    }

    /**
     * Get quiz's results for a course, module (all users for admins otherwise only for current user)
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForAModule($courseId, $moduleId)
    {
        return $this->getQuizResultsForAUserAndAQuiz(null, $courseId, $moduleId, null, null);
    }

    /**
     * Get quiz's results for a course (all users for admins otherwise only for current user)
     * @Route("/api/lms/quizresults/{courseId}",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResultsForACourse($courseId)
    {
        return $this->getQuizResultsForAUserAndAQuiz(null, $courseId, null, null, null);
    }

    /**
     * Get quiz's results  (all users for admins otherwise only for current user)
     * @Route("/api/lms/quizresults",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizResults()
    {
        return $this->getQuizResultsForAUserAndAQuiz(
            null,
            $_GET['course'] ?? null,
            $_GET['module'] ??  null,
            $_GET['activity'] ?? null,
            null
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
        if (!isset($_POST[QuizManager::RESULT_LABEL])) {
            return new ApiResponse(
                [QuizManager::STATUS_LABEL => QuizManager::STATUS_CODE_ERROR,
                QuizManager::MESSAGE_LABEL => 'you must define $_POST[\''.QuizManager::RESULT_LABEL.'\']']
            );
        }

        $result = $this->getService(QuizManager::class)
                    ->saveQuizResultForAUserAndAQuiz($userId, $courseId, $moduleId, $activityId, $quizId, floatval($_POST['result']));
        $code = ($result[QuizManager::STATUS_LABEL] == QuizManager::STATUS_CODE_OK)
            ? 200 // OK
            : 400 // bad request or error
        ;
        return new ApiResponse(['code' => $code]+$result, $code);
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
        foreach ([QuizManager::RESULT_LABEL,'course','module','activity'] as $key) {
            if (!isset($_POST[$key])) {
                $code= 400;
                return new ApiResponse(
                    [
                        'code' => $code,
                        QuizManager::STATUS_LABEL => QuizManager::STATUS_CODE_ERROR,
                        QuizManager::MESSAGE_LABEL => 'you must define $_POST[\''.$key.'\']'
                    ],
                    $code
                );
            }
        }

        return $this->saveQuizResultForAUserAndAQuiz(
            $userId,
            $_POST['course'],
            $_POST['module'],
            $_POST['activity'],
            $quizId,
            floatval($_POST[QuizManager::RESULT_LABEL])
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
        $result = $this->getService(QuizManager::class)
                ->deleteQuizResults($userId, $courseId, $moduleId, $activityId, $quizId);
        $code = ($result[QuizManager::STATUS_LABEL] == QuizManager::STATUS_CODE_OK)
            ? 200 // OK
            : (
                ($result[QuizManager::STATUS_LABEL] == QuizManager::STATUS_CODE_ERROR)
                ? 500 // server error
                : 400 // no result
            );
        return new ApiResponse(['code' => $code]+$result, $code);
    }

    /**
     * delete quiz's result for a user and for course, module, activity
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}/{activityId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAUserAndAnActivity($userId, $courseId, $moduleId, $activityId)
    {
        return $this->deleteQuizResultsForAUserAndAQuiz($userId, $courseId, $moduleId, $activityId, null);
    }
    /**
     * delete quiz's result for a user and for course, module
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}/{moduleId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAUserAndAModule($userId, $courseId, $moduleId)
    {
        return $this->deleteQuizResultsForAUserAndAQuiz($userId, $courseId, $moduleId, null, null);
    }
    /**
     * delete quiz's result for a user and for course
     * @Route("/api/lms/users/{userId}/quizresults/{courseId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAUserAndACourse($userId, $courseId)
    {
        return $this->deleteQuizResultsForAUserAndAQuiz($userId, $courseId, null, null, null);
    }
    /**
     * delete quiz's result for a user
     * @Route("/api/lms/users/{userId}/quizresults",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAUser($userId)
    {
        return $this->deleteQuizResultsForAUserAndAQuiz($userId, null, null, null, null);
    }

    /**
     * delete quiz's result for all users and for course, module, activity and quizId
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}/{activityId}/{quizId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAQuiz($courseId, $moduleId, $activityId, $quizId)
    {
        return $this->deleteQuizResultsForAUserAndAQuiz(null, $courseId, $moduleId, $activityId, $quizId);
    }

    /**
     * delete quiz's result for all users and for course, module, activity
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}/{activityId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAnActivity($courseId, $moduleId, $activityId)
    {
        return $this->deleteQuizResultsForAUserAndAQuiz(null, $courseId, $moduleId, $activityId, null);
    }

    /**
     * delete quiz's result for all users and for course, module
     * @Route("/api/lms/quizresults/{courseId}/{moduleId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForAModule($courseId, $moduleId)
    {
        return $this->deleteQuizResultsForAUserAndAQuiz(null, $courseId, $moduleId, null, null);
    }

    /**
     * delete quiz's result for all users and for course
     * @Route("/api/lms/quizresults/{courseId}",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResultsForACourse($courseId)
    {
        return $this->deleteQuizResultsForAUserAndAQuiz(null, $courseId, null, null, null);
    }

    /**
     * delete quiz's result for all users
     * @Route("/api/lms/quizresults",methods={"DELETE"},options={"acl":{"+"}})
     */
    public function deleteQuizResults()
    {
        return $this->deleteQuizResultsForAUserAndAQuiz(null, null, null, null, null);
    }

    /**
     * @Route("/api/lms/activity-navigation-conditions/{courseId}/{moduleId}/{activityId}",options={"acl":{"public","+"}})
     */
    public function checkActivityNavigationConditions($courseId, $moduleId, $activityId)
    {
        /* start buffer for api */
        ob_start();
        $result = $this->getService(ConditionsChecker::class)
            ->checkActivityNavigationConditions($courseId, $moduleId, $activityId);

        // error + fetch trigger_errors on message
        $triggerErrorsMessage = ob_get_contents() ;
        ob_get_clean();
        if (!empty($triggerErrorsMessage)) {
            $result->addMessage($triggerErrorsMessage);
        }

        $code = ($result->getErrorStatus())
            ? 400 // Not OK
            : 200; //OK
        return new ApiResponse(['code'=>$code]+$result->jsonSerialize(), $code);
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

        $urlCheckConditions = $this->wiki->Href('', 'api/lms/activity-navigation-conditions/{courseId}/{moduleId}/{activityId}');
        $output .= '<br />The following code :<br />';
        $output .= 'GET <code>'.$urlCheckConditions.'</code><br />';
        $output .= 'gives for {activity} of {module} of {course} for the current user :<br />';
        $output .= '<code>[code:HTML_CODE,<br />';
        $output .= 'conditionsMet:true/false,<br />';
        $output .= 'errorStatus:true/false",<br />';
        $output .= 'reactionsNeeded:true/false,<br />';
        $output .= '(optionnal) url:"https://...",<br />';
        $output .= 'messages:[...],<br />';
        $output .= 'formattedMessages:"<.....>"]</code><br />';
        return $output;
    }

    /**
     * @Route("/api/reactions/{idreaction}/{id}/{page}/{username}/delete",methods={"GET"},options={"acl":{"public","+"}},priority=4)
     */
    public function deleteReactionByGetMethod($idreaction, $id, $page, $username)
    {
        if ($user = $this->wiki->getUser()) {
            if ($username == $user['name'] || $this->wiki->UserIsAdmin()) {
                $reactionManager = $this->getService(ReactionManager::class);
                $reactionManager->deleteUserReaction($page, $idreaction, $id, $username);
                // check if deleted
                $reactions = $reactionManager->getReactions($page, [$idreaction], $username);
                if (!empty($reactions)) {
                    $uniqueId = "$idreaction|$page";
                    if (!empty($reactions[$uniqueId]['reactions'])) {
                        $notDeletedReactions = array_filter(
                            $reactions[$uniqueId]['reactions'],
                            function ($reaction) use ($id) {
                                return isset($reaction[$id]) && $reaction[$id] == $id;
                            }
                        );
                        if (!empty($notDeletedReactions)) {
                            return new ApiResponse(
                                ['error' => 'reaction not deleted'],
                                Response::HTTP_INTERNAL_SERVER_ERROR
                            );
                        }
                    }
                }
                return new ApiResponse(
                    [
                        'idReaction'=>$idreaction,
                        'id'=>$id,
                        'page' => $page,
                        'user'=> $username,
                    ],
                    Response::HTTP_OK
                );
            } else {
                return new ApiResponse(
                    ['error' => 'Seul les admins ou l\'utilisateur concerné peuvent supprimer les réactions.'],
                    Response::HTTP_UNAUTHORIZED
                );
            }
        } else {
            return new ApiResponse(
                ['error' => 'Vous devez être connecté pour supprimer les réactions.'],
                Response::HTTP_UNAUTHORIZED
            );
        }
    }

    /**
     * @Route("/api/reactions", methods={"POST"}, options={"acl":{"public", "+"}},priority=4)
     */
    public function addReactionFromUser()
    {
        if ($user = $this->wiki->getUser()) {
            if ($_POST['username'] == $user['name'] || $this->wiki->UserIsAdmin()) {
                if ($_POST['reactionid']) {
                    if ($_POST['pagetag']) { // save the reaction
                        //get reactions from user for this page
                        $userReactions = $this->getService(ReactionManager::class)->getReactions($_POST['pagetag'], [$_POST['reactionid']], $user['name']);
                        $params = $this->getService(ReactionManager::class)->getActionParameters($_POST['pagetag']);
                        $canRegister = !empty($params[$_POST['reactionid']]);
                        if (!$canRegister &&
                            !empty($params['reactionField']['labels']) &&
                            in_array(strval($_POST['id']), array_keys($params['reactionField']['labels']))) {
                            $entry = $this->getService(EntryManager::class)->getOne($_POST['pagetag']);
                            $form = $this->getService(FormManager::class)->getOne($entry['id_typeannonce']);
                            $fieldTemplate = array_filter($form['template'], function ($fTemplate) {
                                return $fTemplate[0] == 'reactions';
                            });
                            $fieldTemplate = $fieldTemplate[array_key_first($fieldTemplate)];
                            $canRegister = (trim($fieldTemplate[1]) == strval($_POST['reactionid']));
                        }
                        if ($canRegister) {
                            // un choix de vote est fait
                            if ($_POST['id']) {
                                // test if limits wherer put
                                if (!empty($params['maxreaction']) && count($userReactions)>= $params['maxreaction']) {
                                    return new ApiResponse(
                                        ['error' => 'Seulement '.$params['maxreaction'].' réaction(s) possible(s). Vous pouvez désélectionner une de vos réactions pour changer.'],
                                        Response::HTTP_UNAUTHORIZED
                                    );
                                } else {
                                    $reactionValues = [
                                        'userName' => $user['name'],
                                        'reactionId' => $_POST['reactionid'],
                                        'id' => $_POST['id'],
                                        'date' => date('Y-m-d H:i:s'),
                                    ];
                                    $this->getService(ReactionManager::class)->addUserReaction(
                                        $_POST['pagetag'],
                                        $reactionValues
                                    );
                                    // hurra, the reaction is saved!
                                    return new ApiResponse(
                                        $reactionValues,
                                        Response::HTTP_OK
                                    );
                                }
                            } else {
                                return new ApiResponse(
                                    ['error' => 'Il faut renseigner une valeur de reaction (id).'],
                                    Response::HTTP_BAD_REQUEST
                                );
                            }
                        }
                        return new ApiResponse(
                            ['error' => "'".strval($_POST['reactionid']) . "' n'est pas une réaction déclarée sur la page '".strval($_POST['pagetag'])."'"],
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    } else {
                        return new ApiResponse(
                            ['error' => 'Il faut renseigner une page wiki contenant la réaction.'],
                            Response::HTTP_BAD_REQUEST
                        );
                    }
                } else {
                    return new ApiResponse(
                        ['error' => 'Il faut renseigner un id de la réaction.'],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            } else {
                return new ApiResponse(
                    ['error' => 'Seul les admins ou l\'utilisateur concerné peuvent réagir.'],
                    Response::HTTP_UNAUTHORIZED
                );
            }
        } else {
            return new ApiResponse(
                json_encode(['error' => 'Vous devez être connecté pour réagir.']),
                Response::HTTP_UNAUTHORIZED
            );
        }
    }
}
