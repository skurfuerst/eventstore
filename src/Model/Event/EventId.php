<?php
declare(strict_types=1);
namespace Neos\EventStore\Model\Event;

use Ramsey\Uuid\Uuid;

final class EventId
{
    /**
     * @readonly
     */
    public string $value;
    private function __construct(string $value)
    {
        $this->value = $value;
    }
    public static function create(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
