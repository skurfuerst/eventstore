<?php
declare(strict_types=1);
namespace Neos\EventStore\Tests;

use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\EventStore\CommitResult;
use Neos\EventStore\Model\Event\EventData;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\Event\EventType;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Events;
use Neos\EventStore\ProvidesSetupInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractEventStoreTest extends TestCase
{
    private ?EventStoreInterface $eventStore = null;

    abstract protected function createEventStore(): EventStoreInterface;

    // --- Tests ----

    public function test_commit_increases_version(): void
    {
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('a', 'c')), 'first-stream');
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('d', 'f')), 'first-stream');

        self::assertEventStream($this->loadEvents(), [
            ['sequenceNumber' => 1, 'version' => 0],
            ['sequenceNumber' => 2, 'version' => 1],
            ['sequenceNumber' => 3, 'version' => 2],
            ['sequenceNumber' => 4, 'version' => 3],
            ['sequenceNumber' => 5, 'version' => 4],
            ['sequenceNumber' => 6, 'version' => 5],
        ]);
    }

    public function dataProvider_commit_expectVersion_concurrencyException(): \Generator
    {
        yield ['streamName' => 'nonexisting-stream', ExpectedVersion::STREAM_EXISTS()];
        yield ['streamName' => 'nonexisting-stream', ExpectedVersion::fromVersion(Version::first())];
        yield ['streamName' => 'nonexisting-stream', ExpectedVersion::fromVersion(Version::fromInteger(123))];
        yield ['streamName' => 'existing-stream', ExpectedVersion::NO_STREAM()];
        yield ['streamName' => 'existing-stream', ExpectedVersion::fromVersion(Version::first())];
        yield ['streamName' => 'existing-stream', ExpectedVersion::fromVersion(Version::fromInteger(123))];
    }

    /**
     * @dataProvider dataProvider_commit_expectVersion_concurrencyException
     */
    public function test_commit_expectVersion_concurrencyExceptions(string $streamName, ExpectedVersion $expectedVersion): void
    {
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('a', 'c')), 'existing-stream');

        $this->expectException(ConcurrencyException::class);
        $this->commitEvent(['data' => 'something'], $streamName, $expectedVersion);
    }

    public function dataProvider_commit_expectVersion_success(): \Generator
    {
        yield ['streamName' => 'nonexisting-stream', ExpectedVersion::ANY()];
        yield ['streamName' => 'nonexisting-stream', ExpectedVersion::NO_STREAM()];
        yield ['streamName' => 'existing-stream', ExpectedVersion::ANY()];
        yield ['streamName' => 'existing-stream', ExpectedVersion::STREAM_EXISTS()];
        yield ['streamName' => 'existing-stream', ExpectedVersion::fromVersion(Version::fromInteger(2))];
    }

    /**
     * @dataProvider dataProvider_commit_expectVersion_success
     */
    public function test_commit_expectVersion_success(string $streamName, ExpectedVersion $expectedVersion): void
    {
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('a', 'c')), 'existing-stream');
        $this->commitEvent(['data' => 'something'], $streamName, $expectedVersion);
        $this->expectNotToPerformAssertions();
    }

    public function test_load_returns_all_events(): void
    {
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('a', 'c')), 'first-stream');
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('d', 'f')), 'second-stream');

        self::assertEventStream($this->loadEvents(), [
            ['sequenceNumber' => 1, 'type' => 'SomeEventType', 'data' => 'a', 'metadata' => [], 'streamName' => 'first-stream', 'version' => 0],
            ['sequenceNumber' => 2, 'type' => 'SomeEventType', 'data' => 'b', 'metadata' => [], 'streamName' => 'first-stream', 'version' => 1],
            ['sequenceNumber' => 3, 'type' => 'SomeEventType', 'data' => 'c', 'metadata' => [], 'streamName' => 'first-stream', 'version' => 2],
            ['sequenceNumber' => 4, 'type' => 'SomeEventType', 'data' => 'd', 'metadata' => [], 'streamName' => 'second-stream', 'version' => 0],
            ['sequenceNumber' => 5, 'type' => 'SomeEventType', 'data' => 'e', 'metadata' => [], 'streamName' => 'second-stream', 'version' => 1],
            ['sequenceNumber' => 6, 'type' => 'SomeEventType', 'data' => 'f', 'metadata' => [], 'streamName' => 'second-stream', 'version' => 2],
        ]);
    }

    public function test_load_returns_empty_stream_if_specified_streamName_does_not_exist(): void
    {
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('a', 'c')), 'first-stream');
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('d', 'f')), 'second-stream');

        self::assertEventStream($this->loadEvents(StreamName::fromString('non-existing')), []);
    }

    public function test_load_returns_filtered_events_matching_specified_streamName(): void
    {
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('a', 'c')), 'first-stream');
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('d', 'f')), 'second-stream');

        self::assertEventStream($this->loadEvents(StreamName::fromString('second-stream')), [
            ['sequenceNumber' => 4, 'type' => 'SomeEventType', 'data' => 'd', 'metadata' => [], 'streamName' => 'second-stream', 'version' => 0],
            ['sequenceNumber' => 5, 'type' => 'SomeEventType', 'data' => 'e', 'metadata' => [], 'streamName' => 'second-stream', 'version' => 1],
            ['sequenceNumber' => 6, 'type' => 'SomeEventType', 'data' => 'f', 'metadata' => [], 'streamName' => 'second-stream', 'version' => 2],
        ]);
    }

    public function test_deleteStream_does_not_reset_sequenceNumber(): void
    {
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('a', 'c')), 'first-stream');
        $this->deleteStream('first-stream');
        $this->commitEvents(array_map(static fn ($char) => ['data' => $char], range('d', 'f')), 'second-stream');

        self::assertEventStream($this->loadEvents(), [
            ['sequenceNumber' => 4],
            ['sequenceNumber' => 5],
            ['sequenceNumber' => 6],
        ]);
    }


    // --- Helper methods -----

    final protected function loadEvents(StreamName $streamName = null): EventStreamInterface
    {
        if ($streamName === null) {
            $streamName = VirtualStreamName::all();
        }
        return $this->getEventStore()->load($streamName);
    }

    /**
     * @param array<array{id?: string, type?: string, data?: string, metadata?: array<mixed>}> $events
     * @param string $streamName
     * @param ExpectedVersion|null $expectedVersion
     * @return CommitResult
     */
    final protected function commitEvents(array $events, string $streamName = 'some-stream', ExpectedVersion $expectedVersion = null): CommitResult
    {
        return $this->getEventStore()->commit(StreamName::fromString($streamName), Events::fromArray(array_map(\Closure::fromCallable([$this, 'convertEvent']), $events)), $expectedVersion ?? ExpectedVersion::ANY());
    }

    /**
     * @param array{id?: string, type?: string, data?: string, metadata?: array<mixed>} $event
     * @param string $streamName
     * @param ExpectedVersion|null $expectedVersion
     * @return CommitResult
     */
    final protected function commitEvent(array $event, string $streamName = 'some-stream', ExpectedVersion $expectedVersion = null): CommitResult
    {
        return $this->commitEvents([$event], $streamName, $expectedVersion);
    }

    final protected function deleteStream(string $streamName): void
    {
        $this->getEventStore()->deleteStream(StreamName::fromString($streamName));
    }

    /**
     * @param EventStreamInterface $eventStream
     * @param array<array{id?: string, type?: string, data?: string, metadata?: array<mixed>, streamName?: string, version?: int, sequenceNumber?: int, recordedAt?: \DateTimeInterface}> $expectedEvents
     */
    final protected static function assertEventStream(EventStreamInterface $eventStream, array $expectedEvents): void
    {
        $actualEvents = [];
        $index = 0;
        foreach ($eventStream as $eventEnvelope) {
            $actualEvents[] = self::eventEnvelopeToArray(isset($expectedEvents[$index]) ? array_keys($expectedEvents[$index]) : ['id', 'type', 'data', 'metadata', 'streamName', 'version', 'sequenceNumber', 'recordedAt'], $eventEnvelope);
            $index ++;
        }
        self::assertEquals($expectedEvents, $actualEvents);
    }

    // --- Internal -----

    private function getEventStore(): EventStoreInterface
    {
        if ($this->eventStore === null) {
            $this->eventStore = $this->createEventStore();
            if ($this->eventStore instanceof ProvidesSetupInterface) {
                $this->eventStore->setup();
            }
        }
        return $this->eventStore;
    }

    /**
     * @param string[] $keys
     * @param EventEnvelope $eventEnvelope
     * @return array{id?: string, type?: string, data?: string, metadata?: array<mixed>, streamName?: string, version?: int, sequenceNumber?: int, recordedAt?: \DateTimeInterface}
     */
    private static function eventEnvelopeToArray(array $keys, EventEnvelope $eventEnvelope): array
    {
        $supportedKeys = ['id', 'type', 'data', 'metadata', 'streamName', 'version', 'sequenceNumber', 'recordedAt'];
        $unsupportedKeys = array_diff($keys, $supportedKeys);
        if ($unsupportedKeys !== []) {
            throw new \InvalidArgumentException(sprintf('Invalid key(s) "%s" for expected event. Allowed keys are: "%s"', implode('", "', $unsupportedKeys), implode('", "', $supportedKeys)), 1651755700);
        }
        $actualAsArray = [
            'id' => $eventEnvelope->event->id->value,
            'type' => $eventEnvelope->event->type->value,
            'data' => $eventEnvelope->event->data->value,
            'metadata' => $eventEnvelope->event->metadata->value,
            'streamName' => $eventEnvelope->streamName->value,
            'version' => $eventEnvelope->version->value,
            'sequenceNumber' => $eventEnvelope->sequenceNumber->value,
            'recordedAt' => $eventEnvelope->recordedAt,
        ];
        foreach (array_diff($supportedKeys, $keys) as $unusedKey) {
            unset($actualAsArray[$unusedKey]);
        }
        return $actualAsArray;
    }

    /**
     * @param array{id?: string, type?: string, data?: string, metadata?: array<mixed>} $event
     * @return Event
     */
    private function convertEvent(array $event): Event
    {
        return new Event(isset($event['id']) ? EventId::fromString($event['id']) : EventId::create(), EventType::fromString($event['type'] ?? 'SomeEventType'), EventData::fromString($event['data'] ?? ''), isset($event['metadata']) ? EventMetadata::fromArray($event['metadata']) : EventMetadata::none());
    }
}
