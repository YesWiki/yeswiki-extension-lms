<?php

namespace YesWiki\Lms;

use Carbon\Carbon;
use YesWiki\Lms\Service\CourseManager;

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

    protected const DATE_FORMAT = 'Y-m-d H:i:s';
    protected const TIME_FORMAT = '%H:%I:%S';

    /**
     * constructor
     * @param $values
     * @param string $tag of the extra activity
     * @param string $title
     * @param string $relatedLink (url or pageTag)
     * @param \DateTime $date of the beginning
     * @param \DateInterval $elapsedTime
     * @param Course $course
     * @param Module $module optional
     */
    public function __construct(
        string $tag,
        string $title,
        string $relatedLink = '',
        \DateTime $date,
        \DateInterval $elapsedTime,
        Course $course,
        Module $module = null
    ) {
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
        CourseManager $courseManager
    ): ?ExtraActivityLog {
        $data = json_decode($json, $true) ;
        if (!empty($data['tag'])
            && !empty($data['title'])
            && !empty($data['date'])
            && !empty($data['elapsedTime'])
            && !empty($data['course'])
            ) {
            if (preg_match('/([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/i', $data['elapsedTime'], $matches)) {
                $durationStr = 'PT'.$matches[1].'H'.$matches[2].'M'.$matches[3].'S' ;
            } else {
                $duration = '';
            }
            return new ExtraActivityLog(
                $data['tag'],
                $data['title'],
                $data['relatedLink'] ?? '',
                \DateTime::createFromFormat(self::DATE_FORMAT, $data['date']),
                new \DateInterval($duration),
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

    public function getDate(): \DateTime
    {
        return $this->date ;
    }

    public function getFormattedDate(): string
    {
        return $this->date->format(self::DATE_FORMAT) ;
    }

    public function getElapsedTime(): \DateInterval
    {
        return $this->elapsedTime ;
    }

    public function getFormattedElapsedTime(): string
    {
        return $this->elapsedTime->format(self::TIME_FORMAT) ;
    }

    /**
     * Getter for 'elapsedTime' of the extra activity
     * @return int the duration in minutes or 0 if not defined or not an integer
     */
    public function getDuration(): int
    {
        return Carbon::create(2000, 1, 1, 0)->diffInMinutes(Carbon::create(2000, 1, 1, 0)->add($this->elapsedTime));
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
            array_filter($this->registeredLearnerNames, function ($value) use ($learnerName) {
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
