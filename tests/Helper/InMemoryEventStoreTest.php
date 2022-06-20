<?php
declare(strict_types=1);
namespace Neos\EventStore\Tests\Helper;

use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Helper\InMemoryEventStoreInterface;
use Neos\EventStore\Tests\AbstractEventStoreTest;

final class InMemoryEventStoreTest extends AbstractEventStoreTest
{

    protected function createEventStore(): EventStoreInterface
    {
        return new InMemoryEventStoreInterface();
    }
}
