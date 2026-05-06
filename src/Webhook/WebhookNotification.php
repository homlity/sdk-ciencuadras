<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Webhook;

use Ciencuadras\Sdk\Exception\WebhookException;

final class WebhookNotification
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $payload
     */
    private function __construct(
        private readonly array $headers,
        private readonly array $payload
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public static function fromRequest(string $rawBody, array $headers = []): self
    {
        $trimmedBody = trim($rawBody);
        if ($trimmedBody === '') {
            throw new WebhookException('Webhook body is empty.');
        }

        try {
            $payload = json_decode($trimmedBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new WebhookException('Webhook body must be valid JSON.', 0, $exception);
        }

        if (!is_array($payload)) {
            throw new WebhookException('Webhook payload must decode to an object.');
        }

        return new self(self::normalizeHeaders($headers), $payload);
    }

    public static function fromGlobals(?string $rawBody = null): self
    {
        $body = $rawBody ?? file_get_contents('php://input');
        if ($body === false) {
            throw new WebhookException('Unable to read webhook body from php://input.');
        }

        return self::fromRequest($body, self::headersFromGlobals());
    }

    public function isAuthorized(string $expectedHubId, string $expectedVerifyToken): bool
    {
        $hubId = trim($expectedHubId);
        $verifyToken = trim($expectedVerifyToken);

        if ($hubId === '' || $verifyToken === '') {
            throw new \InvalidArgumentException('Expected HUB.ID and VERIFY-TOKEN are required.');
        }

        return hash_equals($hubId, (string) $this->header('hub.id'))
            && hash_equals($verifyToken, (string) $this->header('verify-token'));
    }

    public function assertAuthorized(string $expectedHubId, string $expectedVerifyToken): void
    {
        if (!$this->isAuthorized($expectedHubId, $expectedVerifyToken)) {
            throw new WebhookException('Webhook headers do not match the configured HUB.ID / VERIFY-TOKEN.');
        }
    }

    public function header(string $name): ?string
    {
        $normalizedName = strtolower($name);
        return $this->headers[$normalizedName] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function task(): array
    {
        $task = $this->payload['task'] ?? null;
        return is_array($task) ? $task : [];
    }

    public function event(): ?string
    {
        $event = $this->task()['event'] ?? null;
        return is_string($event) ? $event : null;
    }

    public function taskStatus(): ?string
    {
        $status = $this->task()['status'] ?? null;
        return is_string($status) ? $status : null;
    }

    public function isListingStatusEvent(): bool
    {
        return $this->event() === 'LISTING_STATUS';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function contentItems(): array
    {
        $content = $this->task()['content'] ?? null;
        if (!is_array($content)) {
            return [];
        }

        return array_values(array_filter($content, static fn (mixed $item): bool => is_array($item)));
    }

    /**
     * Returns the processed results of each listing included in the task.
     *
     * For `LISTING_STATUS` callbacks, this exposes the processing status of the
     * status change operation. The OpenAPI snapshot does not include the final
     * property state (`ACTIVE` / `DELETED`) in the webhook body.
     *
     * @return list<array<string, mixed>>
     */
    public function listingResults(): array
    {
        $errorsByKey = $this->listingErrorsByLookupKey();
        $results = [];

        foreach ($this->contentItems() as $item) {
            $listingId = isset($item['listing_id']) && is_string($item['listing_id']) ? $item['listing_id'] : null;
            $externalCode = isset($item['external_code']) && is_string($item['external_code']) ? $item['external_code'] : null;

            $error = null;
            if ($listingId !== null && array_key_exists($listingId, $errorsByKey)) {
                $error = $errorsByKey[$listingId];
            } elseif ($externalCode !== null && array_key_exists($externalCode, $errorsByKey)) {
                $error = $errorsByKey[$externalCode];
            }

            $results[] = [
                'listing_id' => $listingId,
                'external_code' => $externalCode,
                'fr_property_id' => $item['fr_property_id'] ?? null,
                'processing_status' => $item['status'] ?? null,
                'multimedia' => $item['multimedia'] ?? [],
                'error' => $error,
            ];
        }

        return $results;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listingStatusUpdates(): array
    {
        if (!$this->isListingStatusEvent()) {
            return [];
        }

        return $this->listingResults();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function listingErrorsByLookupKey(): array
    {
        $task = $this->task();
        $messages = $task['messages'] ?? null;
        if (!is_array($messages)) {
            return [];
        }

        $listings = $messages['listings'] ?? null;
        if (!is_array($listings)) {
            return [];
        }

        $indexed = [];
        foreach ($listings as $listing) {
            if (!is_array($listing)) {
                continue;
            }

            if (isset($listing['listing_id']) && is_string($listing['listing_id'])) {
                $indexed[$listing['listing_id']] = $listing['error'] ?? null;
            }

            if (isset($listing['external_code']) && is_string($listing['external_code'])) {
                $indexed[$listing['external_code']] = $listing['error'] ?? null;
            }
        }

        return $indexed;
    }

    /**
     * @return array<string, string>
     */
    private static function headersFromGlobals(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                /** @var array<string, string> $headers */
                return self::normalizeHeaders($headers);
            }
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
                continue;
            }

            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[str_replace('_', '-', $key)] = $value;
            }
        }

        return self::normalizeHeaders($headers);
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower(trim($name))] = trim($value);
        }

        return $normalized;
    }
}
