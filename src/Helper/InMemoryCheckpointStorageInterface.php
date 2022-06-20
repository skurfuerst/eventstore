<?php
declare(strict_types=1);
namespace Neos\EventStore\Helper;

use Neos\EventStore\Exception\CheckpointException;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\SubscriptionId;
use Neos\EventStore\Subscription\CheckpointStorageInterface;

final class InMemoryCheckpointStorageInterface implements CheckpointStorageInterface
{

    private SequenceNumber $sequenceNumber;
    private ?SequenceNumber $pendingSequenceNumber = null;

    /**
     * @var array<string, true>
     */
    private static array $activeTransactions = [];
    /**
     * @readonly
     */
    private SubscriptionId $subscriptionId;
    public function __construct(SubscriptionId $subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
        $this->sequenceNumber = SequenceNumber::none();
    }

    public function beginTransaction(): SequenceNumber
    {
        if ($this->isTransactionActive()) {
            throw CheckpointException::activeTransaction($this->subscriptionId);
        }
        self::$activeTransactions[$this->subscriptionId->value] = true;
        return $this->sequenceNumber;
    }

    public function update(SequenceNumber $sequenceNumber): void
    {
        if (!$this->isTransactionActive()) {
            throw CheckpointException::noActiveTransaction($this->subscriptionId);
        }
        $this->pendingSequenceNumber = $sequenceNumber;
    }

    public function read(): SequenceNumber
    {
        return $this->pendingSequenceNumber ?? $this->sequenceNumber;
    }

    public function commit(): void
    {
        if (!$this->isTransactionActive()) {
            throw CheckpointException::noActiveTransaction($this->subscriptionId);
        }
        if ($this->pendingSequenceNumber !== null) {
            $this->sequenceNumber = $this->pendingSequenceNumber;
            $this->pendingSequenceNumber = null;
        }
        unset(self::$activeTransactions[$this->subscriptionId->value]);
    }

    private function isTransactionActive(): bool
    {
        return array_key_exists($this->subscriptionId->value, self::$activeTransactions);
    }

    public static function _resetTransactions(): void
    {
        self::$activeTransactions = [];
    }
}
