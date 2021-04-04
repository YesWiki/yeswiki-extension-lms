<?php

namespace YesWiki\Lms;

class ExtraActivityLogs extends TimeLogs implements \Countable, \Iterator
{
    // an array of ExtraActivityLog
    protected $values;
    private $position = 0;

    public function __construct(array $values = [])
    {
        parent::__construct($values);
        $this->position = 0;
    }

    public function has(string $tag): bool
    {
        return isset($this->values[$tag]);
    }

    public function get(string $tag): ?ExtraActivityLog
    {
        return ($this->has($tag))
            ? $this->values[$tag]
            : null ;
    }

    public function set(ExtraActivityLog $extraActivityLog)
    {
        $this->values[$extraActivityLog->getTag()] = $extraActivityLog ;
    }

    public function add(ExtraActivityLog $extraActivityLog): bool
    {
        $tag = $extraActivityLog->getTag();
        if ($this->has($tag)) {
            return false ;
        }
        $this->values[$tag] = $extraActivityLog ;
        return true ;
    }

    /* Iterator functions */
    public function current(): ?ExtraActivityLog
    {
        return $this->values[array_keys($this->values)[$this->position]];
    }

    public function key(): string
    {
        return array_keys($this->values)[$this->position];
    }

    public function next()
    {
        ++$this->position;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid():bool
    {
        return isset(array_keys($this->values)[$this->position]);
    }

    /* Countable */
    public function count():int
    {
        return count($this->values);
    }
}
