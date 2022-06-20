<?php
declare(strict_types=1);
namespace Neos\EventStore\CatchUp;

use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Webmozart\Assert\Assert;

final class CatchUp
{
    /**
     * @readonly
     */
    private \Closure $eventHandler;
    /**
     * @readonly
     */
    private CheckpointStorageInterface $checkpointStorage;
    /**
     * @readonly
     */
    private int $batchSize;
    private function __construct(\Closure $eventHandler, CheckpointStorageInterface $checkpointStorage, int $batchSize)
    {
        $this->eventHandler = $eventHandler;
        $this->checkpointStorage = $checkpointStorage;
        $this->batchSize = $batchSize;
        Assert::positiveInteger($batchSize);
    }
    public static function create(\Closure $eventApplier, CheckpointStorageInterface $checkpointStorage): self
    {
        return new self($eventApplier, $checkpointStorage, 1);
    }

    public function withBatchSize(int $batchSize): self
    {
        if ($batchSize === $this->batchSize) {
            return $this;
        }
        return new self($this->eventHandler, $this->checkpointStorage, $batchSize);
    }

    public function run(EventStreamInterface $eventStream): void
    {
        $highestAppliedSequenceNumber = $this->checkpointStorage->acquireLock();
        $iteration = 0;
        try {
            foreach ($eventStream->withMinimumSequenceNumber($highestAppliedSequenceNumber->next()) as $event) {
                if ($event->sequenceNumber->value <= $highestAppliedSequenceNumber->value) {
                    continue;
                }
                ($this->eventHandler)($event);
                $iteration ++;
                if ($this->batchSize === 1 || $iteration % $this->batchSize === 0) {
                    $this->checkpointStorage->updateAndReleaseLock($event->sequenceNumber);
                    $highestAppliedSequenceNumber = $this->checkpointStorage->acquireLock();
                }
            }
        } finally {
            $this->checkpointStorage->updateAndReleaseLock($highestAppliedSequenceNumber);
        }
    }
}
