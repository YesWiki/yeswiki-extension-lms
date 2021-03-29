<?php

namespace YesWiki\Lms;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\DateManager;

class ExtraActivityLog implements \JsonSerializable
{
    protected $tag ;
    protected $title ;
    protected $relatedLink ;
    protected $date ;
    protected $elapsedTime ;
    protected $registeredLearnerNames ;
    protected $course ;
    protected $module ;

    protected $dateManager;

    /**
     * constructor
     * @param DateManager $dateManager
     * @param string $tag of the extra activity
     * @param string $title
     * @param string $relatedLink (url or pageTag)
     * @param Carbon $date of the beginning
     * @param CarbonInterval $elapsedTime
     * @param Course $course
     * @param Module $module optional
     */
    public function __construct(
        DateManager $dateManager,
        string $tag,
        string $title,
        string $relatedLink = '',
        Carbon $date,
        CarbonInterval $elapsedTime,
        Course $course,
        Module $module = null
    ) {
        $this->dateManager = $dateManager;
        $this->tag = $tag;
        $this->title = $title;
        $this->relatedLink = $relatedLink;
        $this->date = $date;
        $this->elapsedTime = $elapsedTime;
        $this->registeredLearnerNames = [];
        $this->course = $course;
        $this->module = $module;
    }

    public static function createFromJSON(
        string $json,
        CourseManager $courseManager,
        DateManager $dateManager
    ): ?ExtraActivityLog {
        $data = json_decode($json, true) ;
        if (!empty($data['tag'])
            && !empty($data['title'])
            && !empty($data['date'])
            && !empty($data['elapsedTime'])
            && !empty($data['course'])
            ) {
            return new ExtraActivityLog(
                $dateManager,
                $data['tag'],
                $data['title'],
                $data['relatedLink'] ?? '',
                $dateManager->createDatetimeFromString($data['date']),
                $dateManager->createIntervalFromString($data['elapsedTime']),
                $courseManager->getCourse($data['course']),
                !empty($data['module']) ? $courseManager->getModule($data['module']):null,
            )  ;
        } else {
            return null;
        }
    }

    public function getTag(): string
    {
        return $this->tag ;
    }

    public function getTitle(): string
    {
        return $this->title ;
    }

    public function getRelatedLink(): string
    {
        return $this->relatedLink ;
    }

    public function getDate(): Carbon
    {
        return $this->date ;
    }

    public function getEndDate(): Carbon
    {
        return $this->date->copy()->add($this->elapsedTime) ;
    }

    public function getFormattedDate(): string
    {
        return $this->dateManager->formatDatetime($this->date);
    }

    public function getElapsedTime(): CarbonInterval
    {
        return $this->elapsedTime ;
    }

    public function getFormattedElapsedTime(): string
    {
        return $this->dateManager->formatTimeWithColons($this->elapsedTime) ;
    }

    /**
     * Getter for 'elapsedTime' of the extra activity
     * @return int the duration in minutes or 0 if not defined or not an integer
     */
    public function getDuration(): int
    {
        return $this->elapsedTime->totalMinutes;
    }

    public function getRegisteredLearnerNames(): array
    {
        return $this->registeredLearnerNames ;
    }

    public function addLearnerName(string $learnerName): bool
    {
        if (in_array($learnerName, $this->registeredLearnerNames)) {
            return false ;
        }
        $this->registeredLearnerNames[] = $learnerName ;
        return true ;
    }

    public function removeLearnerName(string $learnerName): bool
    {
        if (in_array($learnerName, $this->registeredLearnerNames)) {
            $this->registeredLearnerNames = array_filter($this->registeredLearnerNames, function ($value) use ($learnerName) {
                return ($value != $learnerName);
            });
            return true ;
        } else {
            return false ;
        }
    }

    public function jsonSerialize()
    {
        return array_merge(
            [
                'tag' => $this->getTag() ,
                'title' => $this->getTitle() ,
                'relatedLink' => $this->getRelatedLink() ,
                'date' => $this->getFormattedDate() ,
                'elapsedTime' => $this->getFormattedElapsedTime(),
                'course' => $this->course->getTag()],
            !is_null($this->module) ? ['module' => $this->module->getTag()] : []
        );
    }
}
