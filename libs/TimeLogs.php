<?php

namespace YesWiki\Lms;

class TimeLogs
{
    // an array with values
    protected $values;

    /**
     * Progresses constructor
     * @param $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * The values which represents the progresses
     * @return array the values
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
