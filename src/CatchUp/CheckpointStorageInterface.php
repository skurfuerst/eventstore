<?php
declare(strict_types=1);
namespace Neos\EventStore\CatchUp;

use Neos\EventStore\Model\Event\SequenceNumber;

interface CheckpointStorageInterface
{
    public function acquireLock(): SequenceNumber;
    public function updateAndReleaseLock(SequenceNumber $sequenceNumber): void;
    public function getHighestAppliedSequenceNumber(): SequenceNumber;
}
