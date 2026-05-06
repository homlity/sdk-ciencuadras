<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Webhook;

/**
 * Result of a subscribeTargetIfChanged() call.
 *
 * When $subscribed is false the URL was already current and no API call was made.
 * When $subscribed is true a new subscription was posted and $response holds the
 * raw API response — persist $url as the new known state to avoid future calls.
 */
final class SubscriptionResult
{
    private function __construct(
        public readonly bool $subscribed,
        public readonly string $url,
        public readonly mixed $response,
    ) {}

    public static function unchanged(string $url): self
    {
        return new self(subscribed: false, url: $url, response: null);
    }

    public static function subscribed(string $url, mixed $response): self
    {
        return new self(subscribed: true, url: $url, response: $response);
    }
}
