<?php
declare(strict_types=1);
namespace Neos\EventStore\Model\EventStore;

final class Status
{
    /**
     * @var string[]
     * @readonly
     */
    public array $errors;
    /**
     * @var string[]
     * @readonly
     */
    public array $warnings;
    /**
     * @var string[]
     * @readonly
     */
    public array $notices;
    /**
     * @param string[] $errors
     * @param string[] $warnings
     * @param string[] $notices
     */
    private function __construct(array $errors, array $warnings, array $notices)
    {
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->notices = $notices;
    }
    public static function success(string $notice = null): self
    {
        return new self([], [], $notice !== null ? [$notice] : []);
    }

    public static function error(string $error): self
    {
        return new self([$error], [], []);
    }

    public static function warning(string $warning): self
    {
        return new self([], [$warning], []);
    }
}
