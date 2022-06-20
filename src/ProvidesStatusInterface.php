<?php
declare(strict_types=1);
namespace Neos\EventStore;

use Neos\EventStore\Model\EventStore\Status;

interface ProvidesStatusInterface
{
    public function status(): Status;
}
