<?php
declare(strict_types=1);
namespace Neos\EventStore\Model\EventStream;

use Neos\EventStore\Model\EventStream\VirtualStreamType;
use Webmozart\Assert\Assert;

/**
 * A Virtual stream name synthesize multiple streams and can only be used to _read_ events.
 * Internally, virtual stream names start with a "$"
 */
final class VirtualStreamName
{
    /**
     * @var array<string, self>
     */
    private static array $instances = [];
    /**
     * {@see VirtualStreamType} consts
     *
     * @readonly
     */
    public string $type;
    /**
     * @readonly
     */
    public string $value;
    private function __construct(string $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    private static function constant(string $type, string $value): self
    {
        $id = $type->value . '_' . $value;
        return self::$instances[$id] ?? self::$instances[$id] = new self($type, $value);
    }

    public static function forCategory(string $categoryName): self
    {
        Assert::stringNotEmpty($categoryName);
        return self::constant(VirtualStreamType::CATEGORY, $categoryName);
    }

    public static function forCorrelationId(string $correlationId): self
    {
        Assert::stringNotEmpty($correlationId);
        return self::constant(VirtualStreamType::CORRELATION_ID, $correlationId);
    }

    public static function all(): self
    {
        return self::constant(VirtualStreamType::ALL, '');
    }
}
