<?php
declare(strict_types=1);
namespace Neos\EventStore;

use Neos\EventStore\Model\EventStore\SetupResult;

interface ProvidesSetupInterface
{
    public function setup(): SetupResult;
}
