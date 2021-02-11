<?php

namespace YesWiki\lms;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Wiki;

class Learner
{
    // username of the Learner (the same than the user's one)
    protected $username;
    // fullname of the Learner
    protected $fullname;
    // the Progresses object for all activities/modules and courses
    protected $allProgresses;
    // the associated user entry if exists
    protected $userEntry;

    // the tripleStore to get the progress information
    protected $tripleStore;
    // the entryManager to get the user entry
    protected $entryManager;
    // the Wiki service
    protected $wiki;

    /**
     * Learner constructor
     * A learner always corresponds to a user
     * @param string $username the name of the learner
     * @param TripleStore $tripleStore the TripleStore service
     * @param EntryManager $entryManager the EntryManager service
     * @param Wiki $wiki the Wiki service
     */
    public function __construct(
        string $username,
        TripleStore $tripleStore,
        EntryManager $entryManager,
        Wiki $wiki
    ) {
        $this->username = $username;
        $this->tripleStore = $tripleStore;
        $this->entryManager = $entryManager;
        $this->wiki = $wiki;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Get the associated user entry of the learner
     * @return array the user entry or an empty array if the user doesn't have
     */
    public function getUserEntry(): array
    {
        // lazy loading
        if (is_null($this->userEntry)) {
            $this->userEntry = $this->entryManager->getOne($this->username);
            if (!$this->userEntry) {
                // if no associated user entry, we assign an empty array to avoid other loadings
                $this->userEntry = [];
            }
        }
        return $this->userEntry;
    }

    /**
     * Get the full name of the learner
     * His full name is normally the 'bf_title' field of its user entry, but if he has any, the username will be
     * returned
     * @return string if the learner has a user entry return the 'bf_title', otherwise its username
     */
    public function getFullName(): string
    {
        return !empty($this->getUserEntry()) && !empty($this->getUserEntry()['bf_titre']) ?
            $this->getUserEntry()['bf_titre']
            : $this->getUsername();
    }

    /**
     * Get the tag of the learner user entry
     * @return string|null if the learner has a user entry return its tag, otherwise return null
     */
    public function getUserEntryTag(): ?string
    {
        return !empty($this->getUserEntry()) ?
            // the user entry tag is always the username
            $this->getUsername()
            : null;
    }

    /**
     * Does the learner is a wiki admin ?
     * (TODO if needed, it can evolved in isInstructor but for the moment instructors are wiki admins)
     * @return bool the answer
     */
    public function isAdmin(): bool
    {
        return $this->wiki->userIsAdmin($this->username);
    }

    public function getAllProgresses(): Progresses
    {
        // lazy loading
        if (is_null($this->allProgresses)) {
            $results = $this->tripleStore->getAll(
                $this->username,
                'https://yeswiki.net/vocabulary/progress',
                '',
                ''
            );
            $this->allProgresses = new Progresses(
                array_map(function ($result) {
                    // decode the json which have the progress information
                    $progress = json_decode($result['value'], true);
                    // keep the learner username in the progress
                    $progress['username'] = $this->username;
                    return $progress;
                }, $results)
            );
        } else {
            return $this->allProgresses;
        }
        return $this->allProgresses;
    }

    public function saveProgress(Course $course, Module $module, ?Activity $activity): bool
    {
        $progress = [
                'course' => $course->getTag(),
                'module' => $module->getTag()
            ]
            + ($activity ?
                ['activity' => $activity->getTag()]
                : [])
            + ['log_time' => date('Y-m-d H:i:s', time())];
        $resultState = $this->tripleStore->create(
                $this->username,
                'https://yeswiki.net/vocabulary/progress',
                json_encode($progress),
                '',
                ''
            ) == 0;
        if ($resultState) {
            $this->allProgresses = null; // because Progresses are not up to date
        }
        return $resultState;
    }

    public function saveElapsedTime(Course $course, Module $module, ?Activity $activity, ?CarbonInterval $time): bool
    {
        $like = '%"course":"' . $course->getTag() . '","module":"' . $module->getTag() . '"' .
            (($activity) ? ',"activity":"' . $activity->getTag() . '"' : ',"log_time"')
            . '%'; // if no activity, we are looking for the time attribute just after the module one
        $results = $this->tripleStore->getMatching(
            $this->getUsername(),
            'https://yeswiki.net/vocabulary/progress',
            $like,
            '=',
            '=',
            'LIKE'
        );
        if ($result = array_shift($results)){
            $oldValueJson = $result['value'];
            $oldvalue = json_decode($oldValueJson, true);
            if ($time == null){
                // if time is null, reset the elapsed_time attribute
                if (isset($oldvalue['elapsed_time'])) {
                    unset($oldvalue['elapsed_time']);
                }
                $newvalue = $oldvalue;
            } else {
                // otherwise, update it
                $newvalue = array_merge(
                    $oldvalue,
                    ['elapsed_time' => $time->format('%H:%I:%S')]
                );
            }
            $update = $this->tripleStore->update(
                $this->getUsername(),
                'https://yeswiki.net/vocabulary/progress',
                $oldValueJson,
                json_encode($newvalue),
                '',
                ''
            );
            // 0 when update is correctly done or 3 when the newValue is the same than oldValue (no update)
            return $update == 0 || $update == 3;
        }
        return false;
    }

    public function resetElapsedTime(Course $course, Module $module, ?Activity $activity): bool
    {
        return $this->saveElapsedTime($course, $module, $activity, null);
    }
}
