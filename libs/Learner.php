<?php

namespace YesWiki\lms;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Learner
{
    // the configuration parameters of YesWiki
    protected $config;
    // userName of the Learner
    protected $userName ;

    
    /**
     * Module constructor
     * @param ParameterBagInterface $config the configuration parameters of YesWiki
     * @param string $userName the name of the learner
     */
    public function __construct(ParameterBagInterface $config, string $userName)
    {
        $this->config = $config;
        $this->userName = $userName;
    }

    public function getName()
    {
        return $this->userName ;
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
