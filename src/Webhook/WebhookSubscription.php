<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Webhook;

final class WebhookSubscription
{
    /**
     * @return array{target: string}
     */
    public static function target(string $targetUrl): array
    {
        $normalizedTarget = trim($targetUrl);
        if ($normalizedTarget === '') {
            throw new \InvalidArgumentException('Webhook target URL is required.');
        }

        if (filter_var($normalizedTarget, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Webhook target URL must be a valid absolute URL.');
        }

        return ['target' => $normalizedTarget];
    }
}
