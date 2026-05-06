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

$listing = [
    'external_code' => 'INT-1001',
    'client_id' => 'df03d199-be5c-4c5c-98f6-849361cb7fae',
    'offer' => 'sell',
    'property_type' => 'house',
    'description' => 'Casa amplia y moderna.',
    'price' => 450000000,
    'area' => 120,
    'address' => [
        'address' => 'Calle 12 # 34-56',
    ],
    'locations' => [
        'location_point' => [
            'latitude' => 4.729795079,
            'longitude' => -74.044724493,
        ],
        'location_main_id' => '1895e0a3-60b8-4a9d-858d-f2c7297b48b2',
        'view_map' => 2,
    ],
    'listing_contact' => [
        'emails' => [
            ['email' => 'ventas@midominio.com', 'is_main' => true, 'sort_order' => 0],
        ],
        'phones' => [
            ['phone' => '+573001112233', 'is_click_to_call' => true, 'sort_order' => 0],
        ],
    ],
    'photos' => [
        ['sort_order' => 1, 'is_main' => true, 'image' => 'https://example.com/image-1.jpg'],
    ],
];

$response = $sdk->listings()->create($listing);
print_r($response);
