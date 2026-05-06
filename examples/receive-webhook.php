<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ciencuadras\Sdk\Exception\WebhookException;
use Ciencuadras\Sdk\Webhook\WebhookNotification;

$expectedHubId = getenv('CIENCUADRAS_WEBHOOK_HUB_ID') ?: '';
$expectedVerifyToken = getenv('CIENCUADRAS_WEBHOOK_VERIFY_TOKEN') ?: '';

header('Content-Type: application/json');

try {
    $notification = WebhookNotification::fromGlobals();
    $notification->assertAuthorized($expectedHubId, $expectedVerifyToken);

    $payload = [
        'received' => true,
        'event' => $notification->event(),
        'task_status' => $notification->taskStatus(),
        'listing_results' => $notification->listingResults(),
    ];

    http_response_code(200);
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (WebhookException|\InvalidArgumentException $exception) {
    http_response_code(401);
    echo json_encode([
        'received' => false,
        'error' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
