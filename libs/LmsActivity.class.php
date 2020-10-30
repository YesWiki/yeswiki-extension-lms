<?php

namespace YesWiki;

class LmsActivity
{
    protected $wiki = ''; // give access to the main wiki object

    public function __construct($wiki)
    {
        $this->wiki = $wiki;
    }
}
