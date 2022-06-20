<?php
declare(strict_types=1);
namespace Neos\EventStore\Model\EventStore;

use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event\Version;

final class CommitResult
{
    /**
     * @readonly
     */
    public Version $highestCommittedVersion;
    /**
     * @readonly
     */
    public SequenceNumber $highestCommittedSequenceNumber;
    public function __construct(Version        $highestCommittedVersion, SequenceNumber $highestCommittedSequenceNumber)
    {
        $this->highestCommittedVersion = $highestCommittedVersion;
        $this->highestCommittedSequenceNumber = $highestCommittedSequenceNumber;
    }
}
