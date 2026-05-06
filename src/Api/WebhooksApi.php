<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Api;

use Ciencuadras\Sdk\Webhook\SubscriptionResult;
use Ciencuadras\Sdk\Webhook\WebhookSubscription;

final class WebhooksApi extends BaseApi
{
    /**
     * POST /webhook/{id}/subscribe
     *
     * @param array<string, mixed> $payload
     */
    public function subscribe(string $integratorId, array $payload): mixed
    {
        return $this->send('POST', '/webhook/' . $this->encodePath($integratorId) . '/subscribe', [
            'json' => $payload,
        ]);
    }

    /**
     * Convenience wrapper for the common subscription body expected by Ciencuadras.
     */
    public function subscribeTarget(string $integratorId, string $targetUrl): mixed
    {
        return $this->subscribe($integratorId, WebhookSubscription::target($targetUrl));
    }

    /**
     * Subscribe only when the target URL has changed from the last known value.
     *
     * Because Ciencuadras has no GET endpoint to query the current subscription,
     * the caller must supply $knownUrl (the URL stored after the previous
     * successful call) so the SDK can decide whether an API call is needed.
     *
     * Usage:
     *
     *   $result = $sdk->webhooks()->subscribeTargetIfChanged(
     *       integratorId: $id,
     *       targetUrl: 'https://myapp.com/webhooks/ciencuadras',
     *       knownUrl: Cache::get('ciencuadras_webhook_url'),
     *   );
     *
     *   if ($result->subscribed) {
     *       Cache::set('ciencuadras_webhook_url', $result->url);
     *   }
     *
     * @param string|null $knownUrl Last URL successfully subscribed, or null if unknown.
     */
    public function subscribeTargetIfChanged(
        string $integratorId,
        string $targetUrl,
        ?string $knownUrl = null,
    ): SubscriptionResult {
        $normalizedTarget = WebhookSubscription::target($targetUrl)['target'];

        if ($knownUrl !== null && $knownUrl === $normalizedTarget) {
            return SubscriptionResult::unchanged($normalizedTarget);
        }

        $response = $this->subscribe($integratorId, ['target' => $normalizedTarget]);

        return SubscriptionResult::subscribed($normalizedTarget, $response);
    }

    /**
     * POST /webhook/{id}/unsubscribe
     */
    public function unsubscribe(string $integratorId): mixed
    {
        return $this->send('POST', '/webhook/' . $this->encodePath($integratorId) . '/unsubscribe');
    }

    /**
     * POST /api/ciencuadras/
     *
     * Used for webhook event delivery/verification headers defined by OpenAPI.
     *
     * @param array<string, mixed> $payload
     */
    public function postEvent(string $hubId, string $verifyToken, array $payload): mixed
    {
        return $this->send('POST', '/api/ciencuadras/', [
            'headers' => [
                'HUB.ID' => $hubId,
                'VERIFY-TOKEN' => $verifyToken,
            ],
            'json' => $payload,
        ]);
    }
}
