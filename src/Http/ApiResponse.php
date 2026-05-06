<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Http;

final class ApiResponse
{
    private bool $jsonDecoded = false;
    private mixed $decodedJson = null;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly string $body
    ) {
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function json(): mixed
    {
        if ($this->jsonDecoded) {
            return $this->decodedJson;
        }

        $this->jsonDecoded = true;

        if ($this->body === '') {
            $this->decodedJson = null;
            return $this->decodedJson;
        }

        try {
            $this->decodedJson = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->decodedJson = null;
        }

        return $this->decodedJson;
    }
}
