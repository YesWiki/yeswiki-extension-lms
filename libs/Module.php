<?php
/**
 * LMS Module class
 */

namespace YesWiki\Lms;

use Carbon\Carbon;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Lms\CourseStructure;
use YesWiki\Wiki;

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
    protected $duration; // time in hour necessary for completing the module
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
            $activitiesTagsId = 'checkboxfiche' . $this->config->get('lms_config')['activity_form_id'] . 'bf_activites';
            $this->activities = empty($this->getField($activitiesTagsId)) ?
                [] :
                array_map(
                    function ($activityTag) {
                        return new Activity($this->config, $this->entryManager, $activityTag);
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
        return ($foundIndex === false || $foundIndex === count($this->getActivities()) -1) ?
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
        return !empty($this->getActivities()) ?
            $this->getActivities()[array_key_last($this->getActivities())]->getTag()
            : null;
    }

    /**
     * Get the module description
     * @return string the module description
     */
    public function getDescription(): string
    {
        return $this->getField('bf_description');
    }

    /**
     * calculate duration of the module, in hours, based on inside activities
     *
     * @return string duration in hours
     */
    public function getDuration()
    {
        // lazy loading
        if (is_null($this->duration)) {
            $time = 0;
            $activities = $this->getActivities();
            foreach ($activities as $activity) {
                if (!empty($activity->getField('bf_duree')) && is_numeric($activity->getField('bf_duree')) && intval($activity->getField('bf_duree')) > 0) {
                    $time = $time + intval($activity->getField('bf_duree'));
                }
            }
            $hours = floor($time / 60);
            $minutes = ($time % 60);
            $this->duration = sprintf('%dh%02d', $hours, $minutes);
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
            if (empty($course)) {
                $this->status = ModuleStatus::UNKNOWN; // if no course associated, we cannot check..
            } else {
                if ($this->getField('listeListeOuinonLmsbf_actif') == 'non') {
                    $this->status = ModuleStatus::CLOSED;
                } else {
                    $d = empty($this->getField('bf_date_ouverture')) ? '' : Carbon::parse($this->getField('bf_date_ouverture'));
                    if (!empty($d) && Carbon::now()->lte($d)) {
                        $this->status = ModuleStatus::TO_BE_OPEN;
                    } else {
                        if (!$course->isModuleScripted()) {
                            $this->status = ModuleStatus::OPEN;
                        } else {
                            // TODO finish the scenarisation
                            // if it's the first module, it is open
                            if (!empty($course->getModules()) && $course->getModules()[0] == $this->getTag()) {
                                $this->status = ModuleStatus::OPEN;
                            } else {
                                // todo : check user progress
                                $this->status = ModuleStatus::NOT_ACCESSIBLE;
                            }
                        }
                    }
                }
            }
        }
        return $this->status;
    }

    /**
     * Check if the module is enable
     * @return boolean is the module enabled ?
     */
    public function isEnabled(): ?bool
    {
        // if no value, return true by defaut
        return ($this->getField('listeListeOuinonLmsbf_actif') != 'non');
    }
}
