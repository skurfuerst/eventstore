<?php
declare(strict_types=1);
namespace Neos\EventStore\Model\Event;

use Webmozart\Assert\Assert;

/**
 * The version of an event within a single, non-virtual stream
 */
final class Version
{
    /**
     * @readonly
     */
    public int $value;
    private function __construct(
        int $value
    ) {
        $this->value = $value;
        Assert::natural($this->value, 'Version has to be a natural integer (>= 0)');
    }

    public static function fromInteger(int $value): self
    {
        return new self($value);
    }

    public static function first(): self
    {
        return new self(0);
    }

    public function next(): self
    {
        return new self($this->value + 1);
    }
}
