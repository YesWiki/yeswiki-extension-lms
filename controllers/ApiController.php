<?php

namespace YesWiki\Lms\Controller;

use Symfony\Component\Routing\Annotation\Route;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\YesWikiController;
use YesWiki\Lms\Service\ActivityConditionsManager;
use YesWiki\Lms\Service\CourseManager;

class ApiController extends YesWikiController
{
    /**
     * Give a token for POST quiz results, can only be called if connected
     * @Route("/api/lms/{course}/{module}/{activity}/quiz/token",methods={"GET"},options={"acl":{"public","+"}})
     */
    public function getQuizzToken($course, $module, $activity)
    {
        return new ApiResponse(
            ['status' => false,'token' => "1234A"]
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

        $urlGetQuizzToken = $this->wiki->Href('lms/{course}/{module}/{activity}/quiz/token', 'api');
        $fullUrlGetQuizzToken = $this->wiki->Href('lms/test-course/test-module/test-activity/quiz/token', 'api');
        $urlSaveQuizzResult = $this->wiki->Href('lms/{course}/{module}/{activity}/quiz', 'api');

        $output .= 'The following code :<br />';
        $output .= 'GET <a href="'. $fullUrlGetQuizzToken .'"><code>'.$urlGetQuizzToken.'</code></a><br />';
        $output .= 'gives a json with token needed for <code>'.$urlSaveQuizzResult.'</code><br />';
        $output .= '<code>["status":true/false,<br />';
        $output .= '"token":"66HF867FHHF9JH"]</code><br />';
        $output .= '<b>You must sent cookies to be connected as learner.</b><br />';

        $output .= '<br />The following code :<br />';
        $output .= 'POST <b><code>'.$urlSaveQuizzResult.'</code></b><br />';
        $output .= 'saves data sent in json in $_POST[\'results\'] with token in $_POST[\'token\']<br />';
        $output .= 'Token obtains with <code>'.$urlGetQuizzToken.'</code><br />';
        $output .= 'Return:<br />';
        $output .= '<code>["status":true/false,<br />';
        $output .= '"message":"error message"]</code><br />';
        return $output;
    }
}
