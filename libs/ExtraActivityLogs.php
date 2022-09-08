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
    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->values[array_keys($this->values)[$this->position]];
    }

    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function key(): string
    {
        return array_keys($this->values)[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset(array_keys($this->values)[$this->position]);
    }

    /* Countable */
    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->values);
    }
}
