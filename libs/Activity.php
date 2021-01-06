<?php

namespace YesWiki\Lms;

use YesWiki\Lms\CourseStructure;

class Activity extends CourseStructure
{
    /**
     * Check if the comments is enable for this activity
     * @return boolean true if the comments are enable
     */
    public function isCommentsEnabled(): ?bool
    {
        return ($this->getField('listeListeOuinonLmsbf_commentaires') == 'oui');
    }
}
