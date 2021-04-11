<?php


namespace YesWiki\Lms\Service;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\Service\UserManager;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\lms\Learner;
use YesWiki\Lms\Module;
use YesWiki\Lms\Progresses;
use YesWiki\Wiki;

class LearnerManager
{
    protected const LMS_TRIPLE_PROPERTY_NAME_PROGRESS =  'https://yeswiki.net/vocabulary/progress' ;

    protected $config;
    protected $wiki;
    protected $userManager;
    protected $entryManager;
    protected $tripleStore;
    protected $dateManager;

    /**
     * LearnerManager constructor
     *
     * @param Wiki $wiki the injected wiki instance
     * @param UserManager $userManager the injected UserManager instance
     * @param EntryManager $entryManager the injected EntryManager instance
     * @param TripleStore $tripleStore the injected TripleStore instance
     * @param DateManager $dateManager the injected DateManager instance
     */
    public function __construct(
        Wiki $wiki,
        UserManager $userManager,
        TripleStore $tripleStore,
        EntryManager $entryManager,
        DateManager $dateManager
    ) {
        $this->wiki = $wiki;
        $this->config = $wiki->config;
        $this->userManager = $userManager;
        $this->entryManager = $entryManager;
        $this->tripleStore = $tripleStore;
        $this->dateManager = $dateManager;
    }

    /**
     * Load a Learner from 'username' or connected user.
     * if empty('username') gives the current logged user
     * if not existing username or not logged, return null
     *
     * @param string $username the username for a specific learner
     * @return Learner|null the Learner or null if not connected or not existing
     */
    public function getLearner(string $username = null): ?Learner
    {
        if (empty($username) || empty($this->userManager->getOneByName($username))) {
            $user = $this->userManager->getLoggedUser();
            return empty($user) ?
                null
                : new Learner($user['name'], $this->entryManager, $this->wiki);
        }
        return new Learner($username, $this->entryManager, $this->wiki);
    }

    public function saveActivityProgress(Course $course, Module $module, Activity $activity): bool
    {
        if (!$course || !$module || !$module->hasActivity($activity->getTag())
            || !$course->hasModule($module->getTag())) {
            return false;
        }
        if (!$this->saveActivityOrModuleProgress($course, $module, $activity)) {
            return false;
        }
        // save also for module if needed
        return $this->saveActivityOrModuleProgress($course, $module, null);
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
        // get the current learner
        $learner = $this->getLearner();
        // doesn't save the progresses for not logged users or admins
        if ($learner && (!$learner->isAdmin() || $this->config['lms_config']['save_progress_for_admins'])) {
            $progress = $this->getOneProgressForLearner($learner, $course, $module, $activity);
            if (empty($progress)) {
                // save the current progress
                return $this->saveProgressForLearner($learner, $course, $module, $activity);
            }
        }
        return false;
    }

    public function getOneProgressForLearner(
        Learner $learner,
        Course $course,
        Module $module,
        ?Activity $activity
    ): ?array {
        $like = '%"course":"' . $course->getTag() . '","module":"' . $module->getTag() .
            ($activity ?
                '","activity":"' . $activity->getTag() . '"%'
                : '","log_time"%'); // if no activity, we are looking for the time attribute just after the module one
        $results = $this->tripleStore->getMatching(
            $learner->getUsername(),
            self::LMS_TRIPLE_PROPERTY_NAME_PROGRESS,
            $like,
            '=',
            '=',
            'LIKE'
        );
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
        $results = $this->tripleStore->getMatching(
            null,
            self::LMS_TRIPLE_PROPERTY_NAME_PROGRESS,
            $like,
            'LIKE',
            '=',
            'LIKE'
        );
        if ($results) {
            return new Progresses(
                array_map(function ($res) {
                    // decode the json which have the progress information
                    $progress = json_decode($res['value'], true);
                    // keep the learner username in the progress
                    $progress = ['username' => $res['resource']] + $progress;
                    return $progress;
                }, $results)
            );
        }
        return new Progresses([]);
    }

    public function getAllProgressesForLearner(Learner $learner): Progresses
    {
        $learnerName = $learner->getUsername() ;
        $results = $this->tripleStore->getAll(
            $learnerName,
            self::LMS_TRIPLE_PROPERTY_NAME_PROGRESS,
            '',
            ''
        );
        if ($results) {
            return new Progresses(
                array_map(function ($result) use ($learnerName) {
                    // decode the json which have the progress information
                    $progress = json_decode($result['value'], true);
                    // keep the learner username in the progress
                    $progress['username'] = $learnerName;
                    return $progress;
                }, $results)
            );
        }
        return new Progresses([]);
    }

    private function saveProgressForLearner(
        Learner $learner,
        Course $course,
        Module $module,
        ?Activity $activity
    ): bool {
        $progress = [
                'course' => $course->getTag(),
                'module' => $module->getTag()
            ]
            + ($activity ?
                ['activity' => $activity->getTag()]
                : [])
            + ['log_time' => $this->dateManager->formatDatetime(Carbon::now())];
        $resultState = $this->tripleStore->create(
            $learner->getUsername(),
            self::LMS_TRIPLE_PROPERTY_NAME_PROGRESS,
            json_encode($progress),
            '',
            ''
        ) == 0;
        return $resultState;
    }

    public function saveElapsedTimeForLearner(
        Learner $learner,
        Course $course,
        Module $module,
        ?Activity $activity,
        ?CarbonInterval $time
    ): bool {
        $like = '%"course":"' . $course->getTag() . '","module":"' . $module->getTag() . '"' .
            (($activity) ? ',"activity":"' . $activity->getTag() . '"' : ',"log_time"')
            . '%'; // if no activity, we are looking for the time attribute just after the module one
        $results = $this->tripleStore->getMatching(
            $learner->getUsername(),
            self::LMS_TRIPLE_PROPERTY_NAME_PROGRESS,
            $like,
            '=',
            '=',
            'LIKE'
        );
        if ($result = array_shift($results)) {
            $oldValueJson = $result['value'];
            $oldValue = json_decode($oldValueJson, true);
            if ($time == null) {
                // if time is null, remove the elapsed_time key
                $newValue = array_diff_key($oldValue, ['elapsed_time' => null]);
            } else {
                // otherwise, update it
                $newValue = array_merge(
                    $oldValue,
                    ['elapsed_time' => $this->dateManager->formatTimeWithColons($time)]
                );
            }
            $update = $this->tripleStore->update(
                $learner->getUsername(),
                self::LMS_TRIPLE_PROPERTY_NAME_PROGRESS,
                $oldValueJson,
                json_encode($newValue),
                '',
                ''
            );
            // 0 when update is correctly done or 3 when the newValue is the same than oldValue (no update)
            return $update == 0 || $update == 3;
        }
        return false;
    }

    public function resetElapsedTimeForLearner(
        Learner $learner,
        Course $course,
        Module $module,
        ?Activity $activity
    ): bool {
        return $this->saveElapsedTimeForLearner($learner, $course, $module, $activity, null);
    }

    
    /**
     * Check if module or activity has been started by a learner
     * @param Course $course
     * @param Module $module
     * @param Activity|null $activity
     * @param Learner|null $learner or current learner is null
     * @param Progresses|null $progresses of current Learner if available
     * @return bool
     *
     */
    public function hasBeenOpenedBy(
        Course $course,
        Module $module,
        ?Activity $activity = null,
        ?Learner $learner = null,
        ?Progresses $progresses = null
    ):bool {
        if (!$learner && !($learner = $this->getLearner())) {
            return false ;
        }
        if ($activity) {
            $courseStructure = $activity;
        } else {
            $courseStructure = $module;
        }
        $status = $courseStructure.hasBeenOpenedBy($learner) ;
        if (!is_null($status)) {
            return $status ;
        }

        if (!$progresses) {
            $progresses = $this->getAllProgressesForLearner($learner);
        }
        // get progress
        $progress = $progresses->getProgressForActivityOrModuleForLearner(
            $learner,
            $course,
            $module,
            $activity
        );

        $status = !empty($progress);
        return $courseStructure.hasBeenOpenedBy($learner, $status);
    }
}
