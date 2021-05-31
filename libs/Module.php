<?php
/**
 * LMS Module class
 */

namespace YesWiki\Lms;

use Carbon\Carbon;
use Carbon\CarbonInterval;

class ModuleStatus
{
    const UNKNOWN = 0;
    const CLOSED = 1;
    const TO_BE_OPEN = 2;
    const OPEN = 3;
    const NOT_ACCESSIBLE = 4;
}

class Module extends CourseStructure
{

    // the next fiels are lazy loaded : don't use direct access to them, call the getters instead
    protected $activities; // activities of the module
    protected $duration; // estimated time to complete the module, it's a CarbonInterval object
    protected $status; // see ModuleStatus constants for the different states

    /**
     * get the activities of the module
     *
     * @return Activity[] the module activities
     */
    public function getActivities(): array
    {
        // lazy loading
        if (is_null($this->activities)) {
            $activitiesTagsId = 'checkboxfiche' . $this->config['lms_config']['activity_form_id'] . 'bf_activites';
            $this->activities = empty($this->getField($activitiesTagsId)) ?
                [] :
                array_map(
                    function ($activityTag) {
                        return new Activity($this->config, $this->entryManager, $this->dateManager, $activityTag);
                    },
                    explode(',', $this->getField($activitiesTagsId))
                );
        }
        return $this->activities;
    }

    /**
     * Check if the module has the activity with the given tag
     * @param $activityTag the activity tag to search
     * @return bool true is found, else otherwise
     */
    public function hasActivity($activityTag): bool
    {
        foreach ($this->getActivities() as $activity) {
            if ($activity->getTag() == $activityTag) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the activity with the given tag
     * @param $activityTag the tag which specified the activity
     * @return Activity|null return null if the activity specified is not found
     */
    public function getActivity($activityTag): ?Activity
    {
        foreach ($this->getActivities() as $activity) {
            if ($activity->getTag() == $activityTag) {
                return $activity;
            }
        }
        return null;
    }

    /**
     * Get the previous activity of the activity with the given tag
     * @param $activityTag the tag which specified the activity
     * @return Activity|null return null if the activity specified is not found or is the first one, otherwise return
     * the previous activity in the module activities
     */
    public function getPreviousActivity($activityTag): ?Activity
    {
        $foundIndex = false;
        foreach ($this->getActivities() as $index => $activity) {
            if ($activity->getTag() == $activityTag) {
                $foundIndex = $index;
            }
        }
        return ($foundIndex === false || $foundIndex === 0) ?
            null
            : $this->getActivities()[$foundIndex - 1];
    }

    /**
     * Get the next activity of the activity with the given tag
     * @param $activityTag the tag which specified the activity
     * @return Activity|null return null if the activity specified is not found or is the last one, otherwise return
     * the next activity in the module activities
     */
    public function getNextActivity($activityTag): ?Activity
    {
        $foundIndex = false;
        foreach ($this->getActivities() as $index => $activity) {
            if ($activity->getTag() == $activityTag) {
                $foundIndex = $index;
            }
        }
        return ($foundIndex === false || $foundIndex === count($this->getActivities()) - 1) ?
            null
            : $this->getActivities()[$foundIndex + 1];
    }

    /**
     * Get the tag of the module's first activity
     * @return string|null return null if the activity list is empty, otherwise the tag of the first activity
     */
    public function getFirstActivityTag(): ?string
    {
        return !empty($this->getActivities()) ?
            $this->getActivities()[array_key_first($this->getActivities())]->getTag()
            : null;
    }

    /**
     * Get the tag of the module's last activity
     * @return string|null return null if the activity list is empty, otherwise the tag of the last activity
     */
    public function getLastActivityTag(): ?string
    {
        $lastActivity = $this->getLastActivity();
        return !empty($lastActivity) ?
            $lastActivity->getTag()
            : null;
    }

    /**
     * Get the the module's last activity
     * @return Activity|null return null if the activity list is empty, otherwise the last activity
     */
    public function getLastActivity(): ?Activity
    {
        return !empty($this->getActivities()) ?
            $this->getActivities()[array_key_last($this->getActivities())]
            : null;
    }

    /**
     * Get the duration of a module by adding the duration of all its activities (when the value is filled and is a
     * valid integer)
     * @return CarbonInterval|null the duration or null if duration is zero or there is no activity with duration
     */
    public function getDuration(): ?CarbonInterval
    {
        // lazy loading
        if (is_null($this->duration)) {
            $count = CarbonInterval::minutes(0);
            foreach ($this->getActivities() as $activity) {
                if ($activity->getDuration()) {
                    $count = $count->add($activity->getDuration());
                }
            }
            $this->duration = $count->totalMinutes != 0 ? $count->cascade() : null;
        }
        return $this->duration;
    }

    /**
     * Check if given module is accessible and open for current user, for a given course.
     * Admins may navigate throught closed modules.
     *
     * @param Course $course the course for which the status is calculated
     * @return int status of this module in the related course
     * @see ModuleStatus for the different states of the return value
     */
    public function getStatus(Course $course): int
    {
        // lazy loading
        if (is_null($this->status)) {
            if (empty($course) || empty($this->getActivities())) {
                $this->status = ModuleStatus::UNKNOWN; // if no course associated or no activity, we cannot check..
            } else {
                if ($this->getField('listeListeOuinonLmsbf_actif') == 'non') {
                    $this->status = ModuleStatus::CLOSED;
                } else {
                    $d = empty($this->getField('bf_date_ouverture')) ?
                        null
                        : Carbon::parse($this->getField('bf_date_ouverture'));
                    if (!empty($d) && Carbon::now()->lte($d)) {
                        $this->status = ModuleStatus::TO_BE_OPEN;
                    } else {
                        // TODO finish the scenarisation
                        $this->status = ModuleStatus::OPEN;
                    }
                }
            }
        }
        return $this->status;
    }

    /**
     * Does the module is accessible by the given learner ?
     * @param Learner|null $learner the given learner or null if the current user is not logged
     * @param Course $course the course for which the rights is checked
     * @return bool the answer
     */
    public function isAccessibleBy(?Learner $learner, Course $course): bool
    {
        return ($learner && $learner->canAccessModule($course, $this))
            || (!$learner && $this->getStatus($course) == ModuleStatus::OPEN);
    }

    /**
     * Getter for 'bf_description' of the module entry
     * @return string the module description or null if not defined
     */
    public function getDescription(): ?string
    {
        return $this->getField('bf_description');
    }

    /**
     * Check if the module is enable
     * @return boolean the answer or if no value defined, return true by default
     */
    public function isEnabled(): ?bool
    {
        // if no value, return true by defaut
        return ($this->getField('listeListeOuinonLmsbf_actif') != 'non');
    }
}
