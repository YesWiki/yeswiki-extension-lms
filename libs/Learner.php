<?php

namespace YesWiki\lms;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Module;

class Learner
{
    // the configuration parameters of YesWiki
    protected ParameterBagInterface $config;
    // userName of the Learner
    protected string $userName;
    // TripleStore
    protected TripleStore $tripleStore;

    /**
     * Module constructor
     * @param ParameterBagInterface $config the configuration parameters of YesWiki
     * @param string $userName the name of the learner
     * @param TripleStore $tripleStore the TrepleStore Service
     */
    public function __construct(ParameterBagInterface $config, string $userName, TripleStore $tripleStore)
    {
        $this->config = $config;
        $this->userName = $userName;
        $this->tripleStore = $tripleStore;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getProgress(Course $course, Module $module, ?Activity $activity): ?array
    {
        if (!$course && !$module && !$activity) {
            return null ;
        }
        $results = $this->tripleStore->getAll($this->userName, 'https://yeswiki.net/vocabulary/progress', '', '') ;
        if (!$results || count($results) == 0) {
            return null ;
        } else {
            // extract Tags
            $courseTag = ($course) ? $course->getTag() : '';
            $moduleTag = ($module) ? $module->getTag() : '';
            $activityTag = ($activity) ? $activity->getTag() : '';
            $results_values = array_map(function ($result) {
                return $result['value'];
            }, $results);
            // json decode
            $results_values = array_map(function ($result_value) {
                return json_decode($result_value, true);
            }, $results_values);
            // filter according course, module and activity
            return array_filter($results_values, function ($value) use ($courseTag, $moduleTag, $activityTag) {
                return ($value['course'] == $courseTag &&
                            $value['module'] == $moduleTag &&
                            $value['activity'] == $activityTag
                        );
            });
        }
        
        if (empty($userName)) {
            return null;
        } else {
            return ['temp' => 'temporary return'];
        }
    }
    public function setProgress(Course $course, Module $module, ?Activity $activity): ?bool
    {
        if ($course) {
            return $this->tripleStore->create($this->userName, 'https://yeswiki.net/vocabulary/progress', json_encode([
                'course' => $course->getTag(),
                'module' => ($module) ? $module->getTag() : '',
                'activity' => ($activity) ? $activity->getTag() : '',
                'time' => time()
                ]), '', '') == 0 ;
        } else {
            return false;
        }
    }
}
