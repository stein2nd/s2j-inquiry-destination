<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Dispatch;

final class DispatchFailedException implements \Throwable
{
    public function __construct(
        private readonly string $message,
        private readonly int $code = 0,
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getFile(): string
    {
        return '';
    }

    public function getLine(): int
    {
        return 0;
    }

    /**
     * @return array<int, mixed>
     */
    public function getTrace(): array
    {
        return array();
    }

    public function getPrevious(): ?\Throwable
    {
        return null;
    }

    public function getTraceAsString(): string
    {
        return '';
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
