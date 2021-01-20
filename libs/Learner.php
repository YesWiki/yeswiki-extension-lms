<?php

namespace YesWiki\lms;

use Carbon\Carbon;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\TripleStore;

class Learner
{
    // username of the Learner
    protected $username;
    // fullname of the Learner
    protected $fullname;
    // the Progresses object for all activities/modules and courses
    protected $allProgresses;

    // the tripleStore to get the progress information
    protected $tripleStore;
    // entry manager
    protected $entryManager;

    /**
     * Module constructor
     * @param string $username the name of the learner
     * @param TripleStore $tripleStore the TripleStore service
     * @param EntryManager $entryManager the EntryManager service
     */
    public function __construct(
        string $username,
        TripleStore $tripleStore,
        EntryManager $entryManager
    ) {
        $this->username = $username;
        $this->tripleStore = $tripleStore;
        $this->entryManager = $entryManager;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    /* Search entries with username if not return tag
     * return string title or tag
     */
    public function getFullName(): string
    {
        if ($this->fullname) {
            return $this->fullname;
        } else {
            $userEntry = $this->entryManager->getOne($this->username);
            if ($userEntry && isset($userEntry['bf_titre'])) {
                return $userEntry['bf_titre'];
            } else {
                return $this->username;
            }
        }
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
            $this->allProgresses = null; // because Progresses are not upto date
        }
        return $resultState;
    }

    public function saveElapsedTime(Course $course, Module $module, ?Activity $activity, \DateInterval $time): bool
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
        if (count($results) == 0) {
            return false;
        }
        foreach ($results as $result) {
            $oldvalueJSON = $result['value'];
            $oldvalue = json_decode($oldvalueJSON, true);
            if ($oldvalue['course'] == $course->getTag() &&
                $oldvalue['module'] == $module->getTag() &&
                (($activity && isset($oldvalue['activity']) && $oldvalue['activity'] == $activity->getTag()) ||
                    (!$activity && !isset($oldvalue['activity'])))) {
                $newvalue = array_merge(
                    $oldvalue,
                    ['elapsed_time' => $time->format('%H:%I:%S')]
                );

                $newvalueJSON = json_encode($newvalue);
                $updateResult = $this->tripleStore->update(
                    $this->getUsername(),
                    'https://yeswiki.net/vocabulary/progress',
                    $oldvalueJSON,
                    $newvalueJSON,
                    '',
                    ''
                );
                // TODO find why we must use twice update function
                if ($updateResult == 0) {
                    $updateResult = $this->tripleStore->update(
                        $this->getUsername(),
                        'https://yeswiki.net/vocabulary/progress',
                        $newvalueJSON, // because value is updated
                        $newvalueJSON,
                        '',
                        ''
                    );
                }
                return ($updateResult == 1 || $updateResult == 3);
            }
        }
        return false;
    }

    public function resetElapsedTime(Course $course, Module $module, ?Activity $activity): bool
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
        if (count($results) == 0) {
            return false;
        }
        foreach ($results as $result) {
            $oldvalueJSON = $result['value'];
            $oldvalue = json_decode($oldvalueJSON, true);
            if ($oldvalue['course'] == $course->getTag() &&
                $oldvalue['module'] == $module->getTag() &&
                (($activity && isset($oldvalue['activity']) && $oldvalue['activity'] == $activity->getTag()) ||
                    (!$activity && !isset($oldvalue['activity'])))) {
                if (isset($oldvalue['elapsed_time'])) {
                    unset($oldvalue['elapsed_time']);
                }
                $newvalue = $oldvalue;

                $newvalueJSON = json_encode($newvalue);
                $updateResult = $this->tripleStore->update(
                    $this->getUsername(),
                    'https://yeswiki.net/vocabulary/progress',
                    $oldvalueJSON,
                    $newvalueJSON,
                    '',
                    ''
                );
                // TODO find why we must use twice update function
                if ($updateResult == 0) {
                    $updateResult = $this->tripleStore->update(
                        $this->getUsername(),
                        'https://yeswiki.net/vocabulary/progress',
                        $newvalueJSON, // because value is updated
                        $newvalueJSON,
                        '',
                        ''
                    );
                }
                return ($updateResult == 1 || $updateResult == 3);
            }
        }
        return false;
    }
}
