<?php
declare(strict_types=1);
namespace Neos\EventStore\Tests\Helper;

use Neos\EventStore\Helper\InMemoryCheckpointStorageInterface;
use Neos\EventStore\Model\SubscriptionId;
use Neos\EventStore\Subscription\CheckpointStorageInterface;
use Neos\EventStore\Tests\AbstractCheckpointStorageTest;

final class InMemoryCheckpointStorageTest extends AbstractCheckpointStorageTest
{

    public function tearDown(): void
    {
        InMemoryCheckpointStorageInterface::_resetTransactions();
    }

    protected function createCheckpointStorage(SubscriptionId $subscriptionId): CheckpointStorageInterface
    {
        return new InMemoryCheckpointStorageInterface($subscriptionId);
    }
}
