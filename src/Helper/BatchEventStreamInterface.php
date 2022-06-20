<?php
declare(strict_types=1);
namespace Neos\EventStore\Helper;

use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\Event\SequenceNumber;

final class BatchEventStreamInterface implements EventStreamInterface
{
    private EventStreamInterface $wrappedEventStream;
    /**
     * @readonly
     */
    private int $batchSize;
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
    private function __construct(EventStreamInterface $wrappedEventStream, int $batchSize, ?SequenceNumber $minimumSequenceNumber, ?SequenceNumber $maximumSequenceNumber, ?int $limit, bool $backwards)
    {
        $this->wrappedEventStream = $wrappedEventStream;
        $this->batchSize = $batchSize;
        $this->minimumSequenceNumber = $minimumSequenceNumber;
        $this->maximumSequenceNumber = $maximumSequenceNumber;
        $this->limit = $limit;
        $this->backwards = $backwards;
        if ($this->wrappedEventStream instanceof self) {
            $this->wrappedEventStream = $this->wrappedEventStream->wrappedEventStream;
        }
    }
    public static function create(EventStreamInterface $wrappedEventStream, int $batchSize): self
    {
        return new self($wrappedEventStream, $batchSize, null, null, null, false);
    }

    public function withMinimumSequenceNumber(SequenceNumber $sequenceNumber): self
    {
        if ($this->minimumSequenceNumber !== null && $sequenceNumber->value === $this->minimumSequenceNumber->value) {
            return $this;
        }
        return new self($this->wrappedEventStream, $this->batchSize, $sequenceNumber, $this->maximumSequenceNumber, $this->limit, $this->backwards);
    }

    public function withMaximumSequenceNumber(SequenceNumber $sequenceNumber): self
    {
        if ($this->maximumSequenceNumber !== null && $sequenceNumber->value === $this->maximumSequenceNumber->value) {
            return $this;
        }
        return new self($this->wrappedEventStream, $this->batchSize, $this->minimumSequenceNumber, $sequenceNumber, $this->limit, $this->backwards);
    }

    public function limit(int $limit): self
    {
        if ($limit === $this->limit) {
            return $this;
        }
        return new self($this->wrappedEventStream, $this->batchSize, $this->minimumSequenceNumber, $this->maximumSequenceNumber, $limit, $this->backwards);
    }

    public function backwards(): self
    {
        if ($this->backwards) {
            return $this;
        }
        return new self($this->wrappedEventStream, $this->batchSize, $this->minimumSequenceNumber, $this->maximumSequenceNumber, $this->limit, true);
    }

    public function getIterator(): \Traversable
    {
        $this->wrappedEventStream = $this->wrappedEventStream->limit($this->batchSize);
        if ($this->minimumSequenceNumber !== null) {
            $this->wrappedEventStream = $this->wrappedEventStream->withMinimumSequenceNumber($this->minimumSequenceNumber);
        }
        if ($this->maximumSequenceNumber !== null) {
            $this->wrappedEventStream = $this->wrappedEventStream->withMaximumSequenceNumber($this->maximumSequenceNumber);
        }
        if ($this->backwards) {
            $this->wrappedEventStream = $this->wrappedEventStream->backwards();
        }
        $iteration = 0;
        do {
            $event = null;
            foreach ($this->wrappedEventStream as $event) {
                yield $event;
                $iteration ++;
                if ($this->limit !== null && $iteration >= $this->limit) {
                    return;
                }
            }
            if ($event !== null && (!$this->backwards || $event->sequenceNumber->value > 1)) {
                $this->wrappedEventStream = $this->backwards ? $this->wrappedEventStream->withMaximumSequenceNumber($event->sequenceNumber->previous()) : $this->wrappedEventStream->withMinimumSequenceNumber($event->sequenceNumber->next());
            }
        } while ($event !== null);
    }
}
