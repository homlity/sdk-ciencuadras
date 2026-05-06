<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\CiencuadrasClient;

$config = new Config(
    apiKey: getenv('CIENCUADRAS_API_KEY') ?: '',
    baseUrl: Config::BASE_URL_PRODUCTION,
    timeoutSeconds: 30
);

$sdk = new CiencuadrasClient($config);

$clientCookie = getenv('CIENCUADRAS_CLIENT_COOKIE') ?: '';

$list = $sdk->listings()->list(
    clientCookie: $clientCookie,
    query: [
        'page' => 1,
        'page_size' => 10,
        'ordering' => '-created',
    ]
);

print_r($list);
