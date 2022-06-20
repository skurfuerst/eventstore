<?php
declare(strict_types=1);
namespace Neos\EventStore\Helper;

use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\Event\SequenceNumber;

final class InMemoryEventStreamInterface implements EventStreamInterface
{

    /**
     * @var EventEnvelope[]
     * @readonly
     */
    private array $events;
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
    /**
     * @param EventEnvelope[] $events
     */
    private function __construct(array $events, ?SequenceNumber $minimumSequenceNumber, ?SequenceNumber $maximumSequenceNumber, ?int $limit, bool $backwards)
    {
        $this->events = $events;
        $this->minimumSequenceNumber = $minimumSequenceNumber;
        $this->maximumSequenceNumber = $maximumSequenceNumber;
        $this->limit = $limit;
        $this->backwards = $backwards;
    }
    public static function create(EventEnvelope ...$events): self
    {
        return new self($events, null, null, null, false);
    }

    public function withMinimumSequenceNumber(SequenceNumber $sequenceNumber): self
    {
        if ($this->minimumSequenceNumber !== null && $sequenceNumber->value === $this->minimumSequenceNumber->value) {
            return $this;
        }
        return new self($this->events, $sequenceNumber, $this->maximumSequenceNumber, $this->limit, $this->backwards);
    }

    public function withMaximumSequenceNumber(SequenceNumber $sequenceNumber): self
    {
        if ($this->maximumSequenceNumber !== null && $sequenceNumber->value === $this->maximumSequenceNumber->value) {
            return $this;
        }
        return new self($this->events, $this->minimumSequenceNumber, $sequenceNumber, $this->limit, $this->backwards);
    }

    public function limit(int $limit): self
    {
        if ($limit === $this->limit) {
            return $this;
        }
        return new self($this->events, $this->minimumSequenceNumber, $this->maximumSequenceNumber, $limit, $this->backwards);
    }

    public function backwards(): self
    {
        if ($this->backwards) {
            return $this;
        }
        return new self($this->events, $this->minimumSequenceNumber, $this->maximumSequenceNumber, $this->limit, true);
    }

    public function getIterator(): \Traversable
    {
        $events = $this->events;
        if ($this->backwards) {
            $events = array_reverse($events);
        }
        $iteration = 0;
        foreach ($events as $event) {
            if ($this->minimumSequenceNumber !== null && $event->sequenceNumber->value < $this->minimumSequenceNumber->value) {
                if ($this->backwards) {
                    return;
                }
                continue;
            }
            if ($this->maximumSequenceNumber !== null && $event->sequenceNumber->value > $this->maximumSequenceNumber->value) {
                if ($this->backwards) {
                    continue;
                }
                return;
            }
            yield $event;
            $iteration ++;
            if ($this->limit !== null && $iteration >= $this->limit) {
                return;
            }
        }
    }
}
