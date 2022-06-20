<?php
declare(strict_types=1);
namespace Neos\EventStore\Helper;

use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\EventStore\CommitResult;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\EventStore\Model\EventStream\MaybeVersion;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\EventStore\Model\EventStream\VirtualStreamType;
use Neos\EventStore\Model\Events;
use Webmozart\Assert\Assert;

final class InMemoryEventStoreInterface implements EventStoreInterface
{
    /**
     * @var EventEnvelope[]
     */
    private array $events = [];

    private ?SequenceNumber $sequenceNumber = null;

    /**
     * @param \Neos\EventStore\Model\EventStream\VirtualStreamName|\Neos\EventStore\Model\Event\StreamName $streamName
     */
    public function load($streamName): EventStreamInterface
    {
        switch (get_class($streamName)) {
            case StreamName::class:
                $events = array_filter($this->events, static fn (EventEnvelope $event) => $event->streamName->equals($streamName));
                break;
            case VirtualStreamName::class:
                switch ($streamName->type) {
                    case VirtualStreamType::ALL:
                        $events = $this->events;
                        break;
                    case VirtualStreamType::CATEGORY:
                        $events = array_filter($this->events, static fn (EventEnvelope $event) => strncmp($event->streamName->value, $streamName->value, strlen($streamName->value)) === 0);
                        break;
                    case VirtualStreamType::CORRELATION_ID:
                        $events = array_filter($this->events, static fn (EventEnvelope $event) => $event->metadata->get('correlationIdentifier') === $streamName->value);
                        break;
                }
                break;
            default:
                $events = $this->events;
                break;
        }
        return InMemoryEventStreamInterface::create(...$events);
    }

    public function commit(StreamName $streamName, Events $events, ExpectedVersion $expectedVersion): CommitResult
    {
        $maybeVersion = $this->getStreamVersion($streamName);
        $expectedVersion->verifyVersion($maybeVersion);
        $version = $maybeVersion->isNothing() ? Version::first() : $maybeVersion->unwrap()->next();
        $now = new \DateTimeImmutable();
        $this->sequenceNumber = $this->sequenceNumber ?? SequenceNumber::fromInteger(1);
        foreach ($events as $event) {
            $this->events[] = new EventEnvelope(
                $event->id,
                $event->type,
                $event->data,
                $event->metadata,
                $streamName,
                $version,
                $this->sequenceNumber,
                $now
            );
            $version = $version->next();
            $this->sequenceNumber = $this->sequenceNumber->next();
        }

        return new CommitResult($version, $this->sequenceNumber);
    }

    public function deleteStream(StreamName $streamName): void
    {
        foreach ($this->events as $index => $event) {
            if ($event->streamName->equals($streamName)) {
                unset($this->events[$index]);
            }
        }
    }

    private function getStreamVersion(StreamName $streamName): MaybeVersion
    {
        /** @var Version|null $version */
        $version = null;
        foreach ($this->events as $event) {
            if ($event->streamName->equals($streamName)) {
                $version = $event->version;
            }
        }
        return MaybeVersion::fromVersionOrNull($version);
    }
}
