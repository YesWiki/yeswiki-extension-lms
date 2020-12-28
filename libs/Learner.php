<?php

namespace YesWiki;

class Learner
{
    protected $wiki; // give access to the main wiki object

    public function __construct($wiki)
    {
        $this->wiki = $wiki;
    }

    public function getProgress($user) {
        if (empty($user)) {
            return false;
        } else {
            return $user;
        }
    }

    // saved from Module
    /*public function getNextActivity($user)
    {
        return !empty($this->getActivities()) ? $this->getActivities()[0] : false;
    }*/
}
