<?php

namespace YesWiki\Lms;

use Carbon\CarbonInterval;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Lms\Service\DateManager;

class Activity extends CourseStructure
{
    // estimated time to complete the module, it's a CarbonInterval object
    protected $duration;

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
        return ($learner && $learner->canAccessActivity($course, $module, $this))
            || $module->getStatus($course) == ModuleStatus::OPEN;
    }
}
