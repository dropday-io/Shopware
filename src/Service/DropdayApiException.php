<?php

declare(strict_types=1);

namespace Dropday\DropdayIntegration\Service;

/**
 * Exception thrown when Dropday API requests fail
 */
class DropdayApiException extends \Exception
{
    private array $errors;

    public function __construct(string $message, array $errors = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}

