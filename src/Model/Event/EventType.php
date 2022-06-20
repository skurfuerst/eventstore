<?php
declare(strict_types=1);
namespace Neos\EventStore\Model\Event;

/// TODO make flyweight
final class EventType
{
    /**
     * @readonly
     */
    public string $value;
    private function __construct(string $value)
    {
        $this->value = $value;
    }
    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
