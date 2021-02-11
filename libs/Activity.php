<?php

namespace YesWiki\Lms;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use \DateInterval;
use YesWiki\Lms\CourseStructure;

class Activity extends CourseStructure
{
    protected $duration; // estimated time to complete the module, it's an integer counting the number of minutes

    /**
     * Check if the comments are enable for this activity
     * @return boolean|null the answer or if no value defined, return false by default
     */
    public function isCommentsEnabled(): bool
    {
        return ($this->getField('listeListeOuinonLmsbf_commentaires') == 'oui');
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
     * @return int the duration in minutes or 0 if not defined or not an integer
     */
    public function getDuration(): int
    {
        // lazy loading
        if (is_null($this->duration)) {
            $duration = $this->getField('bf_duree');
            if ($duration && is_numeric($duration) && is_int(intval($duration))) {
                $this->duration = intval($duration);
            } else {
                $this->duration = 0;
            }
        }
        return $this->duration;
    }
}
