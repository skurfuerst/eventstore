<?php
declare(strict_types=1);
namespace Neos\EventStore\Model;

use Neos\EventStore\Model\Event\EventData;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\Event\EventType;

/**
 * Main model for reading and writing (when reading, it is wrapped in {@see EventEnvelope}.
 */
final class Event
{
    /**
     * @readonly
     */
    public EventId $id;
    /**
     * @readonly
     */
    public EventType $type;
    /**
     * @readonly
     */
    public EventData $data;
    /**
     * @readonly
     */
    public EventMetadata $metadata;
    public function __construct(EventId $id, EventType $type, EventData $data, EventMetadata $metadata)
    {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
        $this->metadata = $metadata;
    }
}
