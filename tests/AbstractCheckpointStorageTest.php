<?php
declare(strict_types=1);
namespace Neos\EventStore\Tests;

use Neos\EventStore\Exception\CheckpointException;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\SubscriptionId;
use Neos\EventStore\ProvidesSetupInterface;
use Neos\EventStore\Subscription\CheckpointStorageInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractCheckpointStorageTest extends TestCase
{
    abstract protected function createCheckpointStorage(SubscriptionId $subscriptionId): CheckpointStorageInterface;

    // --- Tests ----

    public function test_acquireLock_returns_first_sequenceNumber_when_first_called(): void
    {
        $checkpointStorage = $this->getCheckpointStorage(SubscriptionId::fromString('some-subscription'));
        $sequenceNumber = $checkpointStorage->acquireLock();
        self::assertTrue($sequenceNumber->equals(SequenceNumber::none()));
    }

    public function test_acquireLock_fails_if_a_transaction_is_active_already(): void
    {
        $checkpointStorage1 = $this->getCheckpointStorage(SubscriptionId::fromString('some-subscription'));
        $checkpointStorage1->acquireLock();
        $checkpointStorage2 = $this->getCheckpointStorage(SubscriptionId::fromString('some-other-subscription'));
        $checkpointStorage3 = $this->getCheckpointStorage(SubscriptionId::fromString('some-subscription'));
        $checkpointStorage2->acquireLock();

        $this->expectException(CheckpointException::class);
        $checkpointStorage3->acquireLock();
    }

    public function test_updateAndReleaseLock_fails_if_no_transaction_is_active(): void
    {
        $checkpointStorage = $this->getCheckpointStorage(SubscriptionId::fromString('some-subscription'));

        $this->expectException(CheckpointException::class);
        $checkpointStorage->updateAndReleaseLock(SequenceNumber::fromInteger(123));
    }


    public function test_read_returns_first_sequenceNumber_when_first_called(): void
    {
        $checkpointStorage = $this->getCheckpointStorage(SubscriptionId::fromString('some-subscription'));

        $sequenceNumber = $checkpointStorage->read();
        self::assertTrue($sequenceNumber->equals(SequenceNumber::none()));
    }

//    public function test_read_returns_updated_version_before_it_is_committed(): void
//    {
//        $checkpointStorage = $this->getCheckpointStorage(SubscriptionId::fromString('some-subscription'));
//        $checkpointStorage->acquireLock();
//        self::assertSame($checkpointStorage->read()->value, 0);
//        $checkpointStorage->update(SequenceNumber::fromInteger(2));
//        self::assertSame($checkpointStorage->read()->value, 2);
//        $checkpointStorage->commit();
//        self::assertSame($checkpointStorage->read()->value, 2);
//    }

    public function test_commit_fails_if_no_transaction_is_active(): void
    {
        $checkpointStorage = $this->getCheckpointStorage(SubscriptionId::fromString('some-subscription'));

        $this->expectException(CheckpointException::class);
        $checkpointStorage->updateAndReleaseLock(SequenceNumber::fromInteger(123));
    }


    // --- Internal -----

    private function getCheckpointStorage(SubscriptionId $subscriptionId): CheckpointStorageInterface
    {
        $checkpointStorage = $this->createCheckpointStorage($subscriptionId);
        if ($checkpointStorage instanceof ProvidesSetupInterface) {
            $checkpointStorage->setup();
        }
        return $checkpointStorage;
    }


}
