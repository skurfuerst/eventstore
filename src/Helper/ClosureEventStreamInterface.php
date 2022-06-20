<?php
declare(strict_types=1);
namespace Neos\EventStore\Helper;

use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\Event\SequenceNumber;

final class ClosureEventStreamInterface implements EventStreamInterface
{

    /**
     * @readonly
     */
    private \Closure $closure;
    /**
     * @readonly
     */
    private ?SequenceNumber $minimumSequenceNumber;
    /**
     * @readonly
     */
    private ?SequenceNumber $maximumSequenceNumber;
    /**
     * @readonly
     */
    private ?int $limit;
    /**
     * @readonly
     */
    private bool $backwards;
    private function __construct(\Closure $closure, ?SequenceNumber $minimumSequenceNumber, ?SequenceNumber $maximumSequenceNumber, ?int $limit, bool $backwards)
    {
        $this->closure = $closure;
        $this->minimumSequenceNumber = $minimumSequenceNumber;
        $this->maximumSequenceNumber = $maximumSequenceNumber;
        $this->limit = $limit;
        $this->backwards = $backwards;
    }
    public static function create(\Closure $closure): self
    {
        return new self($closure, null, null, null, false);
    }

    public function withMinimumSequenceNumber(SequenceNumber $sequenceNumber): self
    {
        if ($this->minimumSequenceNumber !== null && $sequenceNumber->value === $this->minimumSequenceNumber->value) {
            return $this;
        }
        return new self($this->closure, $sequenceNumber, $this->maximumSequenceNumber, $this->limit, $this->backwards);
    }

    public function withMaximumSequenceNumber(SequenceNumber $sequenceNumber): self
    {
        if ($this->maximumSequenceNumber !== null && $sequenceNumber->value === $this->maximumSequenceNumber->value) {
            return $this;
        }
        return new self($this->closure, $this->minimumSequenceNumber, $sequenceNumber, $this->limit, $this->backwards);
    }

    public function limit(int $limit): self
    {
        if ($limit === $this->limit) {
            return $this;
        }
        return new self($this->closure, $this->minimumSequenceNumber, $this->maximumSequenceNumber, $limit, $this->backwards);
    }

    public function backwards(): self
    {
        if ($this->backwards) {
            return $this;
        }
        return new self($this->closure, $this->minimumSequenceNumber, $this->maximumSequenceNumber, $this->limit, true);
    }

    public function getIterator(): \Traversable
    {
        yield from ($this->closure)($this->minimumSequenceNumber, $this->maximumSequenceNumber, $this->limit, $this->backwards);
    }
}
