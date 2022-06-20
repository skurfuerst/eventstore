<?php
declare(strict_types=1);
namespace Neos\EventStore\Model\EventStream;

final class VirtualStreamType
{
    const ALL = 'all';
    const CATEGORY = 'category';
    const CORRELATION_ID = 'correlation';
}
