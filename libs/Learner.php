<?php

namespace YesWiki\lms;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Core\Service\TripleStore;
use Carbon\Carbon;

class Learner
{
    // username of the Learner
    protected $username;
    // the Progresses object for all activities/modules and courses
    protected $allProgresses;

    // the configuration parameters of YesWiki
    protected $config;
    // the tripleStore to get the progress information
    protected $tripleStore;

    /**
     * Module constructor
     * @param string $username the name of the learner
     * @param ParameterBagInterface $config the configuration parameters of YesWiki
     * @param TripleStore $tripleStore the TripleStore Service
     */
    public function __construct(string $username, ParameterBagInterface $config, TripleStore $tripleStore)
    {
        $this->username = $username;
        $this->config = $config;
        $this->tripleStore = $tripleStore;
    }

    public function getUsername(): string
    {
        return $this->username;
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
        $progress = ['course' => $course->getTag(),
                'module' => $module->getTag()]
            + ($activity ?
                ['activity' => $activity->getTag()]
                : [])
            + ['log_time' => date('Y-m-d H:i:s', time())];
        return $this->tripleStore->create(
            $this->username,
            'https://yeswiki.net/vocabulary/progress',
            json_encode($progress),
            '',
            ''
        ) == 0;
    }

    public function saveElapsedTime(Course $course, Module $module, ?Activity $activity, \DateInterval $time): bool
    {
        $like = '%"course":"' . $course->getTag() . '","module":"' . $module->getTag() .
            ($activity ?
                '","activity":"' . $activity->getTag() . '"%'
                : '","log_time"%'); // if no activity, we are looking for the time attribute just after the module one
        $results = $this->tripleStore->getMatching(
            $this->getUsername(),
            'https://yeswiki.net/vocabulary/progress',
            $like,
            '=',
            '=',
            'LIKE'
        );
        if (count($results) == 0) {
            return false ;
        }
        foreach ($results as $result) {
            $oldvalueJSON = $result['value'] ;
            $oldvalue = json_decode($oldvalueJSON);
            $newvalue = array_merge(
                $oldvalue,
                ['elapsed_time' => $time->format('%H:%I:%S')]
            );

            $newvalueJSON = json_encode($newvalue);
            $this->tripleStore->update(
                $this->getUsername(),
                'https://yeswiki.net/vocabulary/progress',
                $oldvalueJSON,
                $newvalueJSON,
                '',
                ''
            );
        }
        return true ;
    }
}
