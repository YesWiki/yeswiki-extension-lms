<?php


namespace YesWiki\Lms\Service;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\Service\UserManager;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Controller\CourseController;
use YesWiki\Lms\Course;
use YesWiki\lms\Learner;
use YesWiki\Lms\Module;
use YesWiki\Lms\Progresses;


class LearnerManager
{
    protected $config;
    protected $userManager;
    protected $courseManager;
    protected $tripleStore;

    /**
     * LearnerManager constructor
     * @param ParameterBagInterface $config the injected configuration instance
     * @param UserManager $userManager the injected UserManager instance
     * @param CourseManager $courseManager the injected CourseManager instance
     * @param TripleStore $tripleStore the injected TripleStore instance
     */
    public function __construct(ParameterBagInterface $config, UserManager $userManager, CourseManager $courseManager,
        TripleStore $tripleStore)
    {
        $this->config = $config;
        $this->userManager = $userManager;
        $this->tripleStore = $tripleStore;
    }

    /**
     * Load a Learner from 'username' or connected user.
     * if empty('username') gives the current logged user
     * if not existing username or not logged, return null
     *
     * @param string $username the username for a specific learner
     * @return Learner|null the Learner or null if not connected or not existing
     */
    public function getLearner(string $username = ''): ?Learner
    {
        if (empty($username)) {
            $user = $this->userManager->getLoggedUser();
            return empty($user) ?
                null
                : new Learner($user['name'], $this->config, $this->tripleStore);
        }
        return new Learner($username, $this->config, $this->tripleStore);
    }

    public function saveActivityProgress(Course $course, Module $module, Activity $activity): bool
    {
        if (!$course || !$module || !$module->hasActivity($activity->getTag())
            || !$course->hasModule($module->getTag())) {
            return false;
        }
        return $this->saveActivityOrModuleProgress($course, $module, $activity);
    }

    public function saveModuleProgress(Course $course, Module $module): bool
    {
        if (!$course || !$course->hasModule($module->getTag())) {
            return false;
        }
        return $this->saveActivityOrModuleProgress($course, $module, null);
    }

    private function saveActivityOrModuleProgress(Course $course, Module $module, ?Activity $activity): bool
    {
        // get the current learner if the user is connected
        $learner = $this->getLearner();
        if (!$learner) {
            return false;
        }
        $progress = $this->getOneProgressForLearner($learner, $course, $module, $activity);
        if (empty($progress)) {
            // save the current progress
            return $learner->saveProgress($course, $module, $activity);
        }
        return false;
    }

    public function getOneProgressForLearner(Learner $learner, Course $course, Module $module, ?Activity $activity): ?array
    {
        $like = '%"course":"' . $course->getTag() . '","module":"' . $module->getTag() .
            ($activity ?
                '","activity":"' . $activity->getTag() . '"%'
                : '","log_time"%'); // if no activity, we are looking for the time attribute just after the module one
        $results = $this->tripleStore->getMatching($learner->getUsername(), 'https://yeswiki.net/vocabulary/progress',
            $like, '=', '=', 'LIKE');
        if ($results) {
            // decode the json which have the progress information
            $progress = json_decode($results[0]['value'], true);
            // keep the learner username in the progress
            $progress['username'] = $results[0]['resource'];
            return $progress;
        }
        return null;
    }

    public function getProgressesForAllLearners(Course $course): Progresses
    {
        $like = '%"course":"' . $course->getTag() . '"%';
        $results = $this->tripleStore->getMatching(null, 'https://yeswiki.net/vocabulary/progress',
            $like, 'LIKE', '=', 'LIKE');
        if ($results) {
            // json decode
            $results = new Progresses(
                array_map(function ($res) {
                    // decode the json which have the progress information
                    $progress = json_decode($res['value'], true);
                    // keep the learner username in the progress
                    $progress = ['username' => $res['resource']] + $progress;
                    return $progress;
                }, $results)
            );
            return $results;
        }
        return new Progresses([]);
    }
}