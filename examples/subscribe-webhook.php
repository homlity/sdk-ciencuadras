<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\CiencuadrasClient;

$apiKey = getenv('CIENCUADRAS_API_KEY') ?: '';
$webhookId = getenv('CIENCUADRAS_WEBHOOK_ID') ?: '';
$targetUrl = getenv('CIENCUADRAS_WEBHOOK_TARGET') ?: '';

$sdk = new CiencuadrasClient(new Config($apiKey));

$response = $sdk->webhooks()->subscribeTarget($webhookId, $targetUrl);

print_r($response);
