<?php

namespace YesWiki;

class LmsLearner
{
    protected $wiki = ''; // give access to the main wiki object

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
}
