<?php

namespace App\Exceptions;

use RuntimeException;

class AiJsonOutputException extends RuntimeException
{
    public function __construct(
        string $message,
        private ?string $rawOutputText = null,
        private ?int $promptTokens = null,
        private ?int $outputTokens = null,
        private bool $repairUsed = false
    ) {
        parent::__construct($message);
    }

    public function rawOutputText(): ?string
    {
        return $this->rawOutputText;
    }

    public function promptTokens(): ?int
    {
        return $this->promptTokens;
    }

    public function outputTokens(): ?int
    {
        return $this->outputTokens;
    }

    public function repairUsed(): bool
    {
        return $this->repairUsed;
    }
}

