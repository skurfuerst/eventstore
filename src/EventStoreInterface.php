<?php
declare(strict_types=1);
namespace Neos\EventStore;

use Neos\EventStore\Model\EventStore\CommitResult;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\EventStore\Model\Events;

interface EventStoreInterface
{
    /**
     * @param \Neos\EventStore\Model\Event\StreamName|\Neos\EventStore\Model\EventStream\VirtualStreamName $streamName
     */
    public function load($streamName): EventStreamInterface;
    public function commit(StreamName $streamName, Events $events, ExpectedVersion $expectedVersion): CommitResult;
    public function deleteStream(StreamName $streamName): void;
}
