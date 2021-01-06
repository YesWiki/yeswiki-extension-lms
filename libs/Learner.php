<?php

namespace YesWiki\lms;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Core\Service\TripleStore;
use Carbon\Carbon;

class Learner
{
    // username of the Learner
    protected $username;
    // the progresses array for all activities/modules and courses
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

    public function getAllProgresses(): array
    {
        // lazy loading
        if (is_null($this->allProgresses)) {
            $results = $this->tripleStore->getAll($this->username, 'https://yeswiki.net/vocabulary/progress',
                '', '');
            // json decode
            $this->allProgresses = array_map(function ($result) {
                return json_decode($result['value'], true);
            }, $results);
        } else {
            return $this->allProgresses;
        }
    }

    public function getProgressesForActivityOrModule(Course $course, Module $module, ?Activity $activity): array
    {
        return array_filter($this->getAllProgresses(), function ($value) use ($course, $module, $activity) {
            return ($value['course'] == $course->getTag()
                && $value['module'] == $module->getTag()
                && (!$activity || $value['activity'] == $activity->getTag())
            );
        });
    }

    public function findOneProgress(Course $course, Module $module, ?Activity $activity): ?array
    {
        $like = '%"course":"' . $course->getTag() . '","module":"' . $module->getTag() .
            ($activity ?
                '","activity":"' . $activity->getTag() . '"%'
                : '","time":"%'); // if no activity, we are looking for the time attribute just after the module one
        $results = $this->tripleStore->getMatching($this->username, 'https://yeswiki.net/vocabulary/progress',
            $like, '=', '=', 'LIKE');
        if ($results) {
            return json_decode($results[0]['value'], true);
        }
        return null;
    }

    public function addProgress(Course $course, Module $module, ?Activity $activity): bool
    {
        $progress = ['course' => $course->getTag(),
                'module' => $module->getTag()]
            + ($activity ?
                ['activity' => $activity->getTag()]
                : [])
            + ['time' => Carbon::now()->toIso8601String()];
        return $this->tripleStore->create($this->username, 'https://yeswiki.net/vocabulary/progress',
                json_encode($progress), '', '') == 0;
    }
}
