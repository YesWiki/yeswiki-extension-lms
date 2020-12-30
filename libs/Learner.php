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

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getProgress(): ?array
    {
        if (empty($userName)) {
            return null;
        } else {
            return ['temp' => 'temporary return'];
        }
    }
}
