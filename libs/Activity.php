<?php

namespace YesWiki\Lms;

use YesWiki\Lms\CourseStructure;

class Activity extends CourseStructure
{

    /**
     * Get the tag for which this activity is referenced in the LMS menu
     *
     * Indeed, if if the Lms module is configurated for nav tabs, the tabs have the tags 'MyTagX' with X >= 2 and the
     * menu always refer to 'MyTag'
     *
     * @return string if the tabs are configurated, return the tag without its end number (if it has any), otherwise
     * return the tag of the activity
     */
    function getMenuReferenceTag() : string
    {
        return $this->config->get('lms_config')['use_tabs'] ?
            preg_replace('/[0-9]*$/', '', $this->getTag())
            : $this->getTag();
    }
}
