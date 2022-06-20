<?php
declare(strict_types=1);
namespace Neos\EventStore\Model;

use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventStream\EventStreamInterface;

/**
 * returned when iterating over the {@see EventStreamInterface} (read side(
 */
final class EventEnvelope
{
    /**
     * @readonly
     */
    public Event $event;
    /**
     * @readonly
     */
    public StreamName $streamName;
    /**
     * @readonly
     */
    public Version $version;
    /**
     * @readonly
     */
    public SequenceNumber $sequenceNumber;
    /**
     * @readonly
     */
    public \DateTimeImmutable $recordedAt;
    public function __construct(Event $event, StreamName $streamName, Version $version, SequenceNumber $sequenceNumber, \DateTimeImmutable $recordedAt)
    {
        $this->event = $event;
        $this->streamName = $streamName;
        $this->version = $version;
        $this->sequenceNumber = $sequenceNumber;
        $this->recordedAt = $recordedAt;
    }
}
