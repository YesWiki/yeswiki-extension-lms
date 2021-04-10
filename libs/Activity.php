<?php

namespace YesWiki\Lms;

use Carbon\CarbonInterval;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Lms\Service\DateManager;

class ActivityStatus
{
    const UNKNOWN = 0;
    const CLOSED = 1;
    const TO_BE_OPEN = 2;
    const OPEN = 3;
    const NOT_ACCESSIBLE = 4;
}

class Activity extends CourseStructure
{
    // estimated time to complete the module, it's a CarbonInterval object
    protected $duration;
    protected $status; // see ActivityStatus constants for the different states

    /**
     * Check if the comments are enable for this activity
     * @return boolean|null the answer or if no value defined, return true by default
     */
    public function isCommentsEnabled(): bool
    {
        return ($this->getField('listeListeOuinonLmsbf_commentaires') != 'non');
    }

    /**
     * Check if the reactions are enable for this activity
     * @return boolean|null the answer or if no value defined, return false by default
     */
    public function isReactionsEnabled(): bool
    {
        return ($this->getField('listeListeOuinonLmsbf_reactions') == 'oui');
    }

    /**
     * Getter for 'bf_duree' of the activity entry
     * @return CarbonInterval|null the duration or null if duration is zero or the activity has no duration
     */
    public function getDuration(): ?CarbonInterval
    {
        // lazy loading
        if (is_null($this->duration)) {
            $duration = $this->getField('bf_duree');
            if ($duration && is_numeric($duration) && is_int(intval($duration))) {
                $this->duration = $this->dateManager->createIntervalFromMinutes(intval($duration));
            } else {
                $this->duration = null;
            }
        }
        return $this->duration;
    }

    
    /**
     * Does the activity is accessible by the given learner ?
     * @param Learner|null $learner the given learner or null if the current user is not logged
     * @param Course $course the course for which the rights is checked
     * @param Module $module the module for which the rights is checked
     * @return bool the answer
     */
    public function isAccessibleBy(?Learner $learner, Course $course, Module $module): bool
    {
        return ($learner && $learner->isAdmin()) || $this->getStatus($course, $module) == ActivityStatus::OPEN;
    }

    
    /**
     * Check if given activity is accessible and open for current user, for a given course.
     * Admins may navigate throught closed modules.
     *
     * @param Course $course the course for which the status is calculated
     * @param Module $module the course for which the status is calculated
     * @return int status of this module in the related course
     * @see ActivityStatus for the different states of the return value
     */
    public function getStatus(Course $course, Module $module): int
    {
        // lazy loading
        if (is_null($this->status)) {
            if (empty($course) || empty($module)) {
                $this->status = ActivityStatus::UNKNOWN; // if no course associated or no module associated, we cannot check..
            } else {
                switch ($module->getStatus($course)) {
                    case ModuleStatus::CLOSED:
                        $this->status = ActivityStatus::CLOSED;
                        break;
                    case ModuleStatus::TO_BE_OPEN:
                        $this->status = ActivityStatus::TO_BE_OPEN;
                        break;
                    case ModuleStatus::NOT_ACCESSIBLE:
                        $this->status = ActivityStatus::NOT_ACCESSIBLE;
                        break;
                    case ModuleStatus::OPEN:                        
                        // TODO finish the scenarisation
                        $this->status = (is_null($this->scriptedOpenedStatus) 
                                || $this->scriptedOpenedStatus)
                            ? ActivityStatus::OPEN
                            : ActivityStatus::NOT_ACCESSIBLE;
                        break;
                    case ModuleStatus::UNKNOWN:
                    default:
                        $this->status = ActivityStatus::UNKNOWN;
                        break;
                }
            }
        }
        return $this->status;
    }
}
