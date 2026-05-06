<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Tests;

use Ciencuadras\Sdk\Api\ListingsApi;
use Ciencuadras\Sdk\Api\TasksApi;
use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\Http\ApiResponse;
use Ciencuadras\Sdk\Http\HttpClientInterface;
use Ciencuadras\Sdk\Schema\ListingPayloadValidator;
use Ciencuadras\Sdk\Schema\SchemaCatalog;
use PHPUnit\Framework\TestCase;

final class HomlityConfigTest extends TestCase
{
    public function testFromHomlityEnvBuildsSandboxConfigAndEndpoints(): void
    {
        $config = Config::fromHomlityEnv('token', [
            'ciencuadras_environment' => 'sandbox',
            'CIENCUADRAS_SANDBOX_BASE_URL' => 'https://dev-ws-api.ciencuadras.com',
            'CIENCUADRAS_SANDBOX_CREATE_ENDPOINT' => '/api/create',
            'CIENCUADRAS_SANDBOX_UPDATE_ENDPOINT' => '/api/update',
            'CIENCUADRAS_SANDBOX_CONSULT_STATUS_ENDPOINT' => '/api/consult-status',
            'CIENCUADRAS_SANDBOX_CONSULT_PROPERTY_ENDPOINT' => '/api/consult-property',
            'CIENCUADRAS_SANDBOX_CONSULT_ALL_PROPERTIES_ENDPOINT' => '/api/consult-all-properties',
        ]);

        self::assertSame('https://dev-ws-api.ciencuadras.com', $config->baseUrl());
        self::assertSame('/api/create', $config->endpoint('create'));
        self::assertSame('/api/update', $config->endpoint('update'));
        self::assertSame('/api/consult-status', $config->endpoint('consult_status'));
        self::assertSame('/api/consult-property', $config->endpoint('consult_property'));
        self::assertSame('/api/consult-all-properties', $config->endpoint('consult_all_properties'));
    }

    public function testListingsApiUsesConfiguredHomlityPaths(): void
    {
        $config = Config::fromHomlityEnv('token', [
            'ciencuadras_environment' => 'production',
            'CIENCUADRAS_PRODUCTION_BASE_URL' => 'https://ws-api.ciencuadras.com',
            'CIENCUADRAS_PRODUCTION_CREATE_ENDPOINT' => '/api/create',
            'CIENCUADRAS_PRODUCTION_UPDATE_ENDPOINT' => '/api/update',
            'CIENCUADRAS_PRODUCTION_CONSULT_PROPERTY_ENDPOINT' => '/api/consult-property',
            'CIENCUADRAS_PRODUCTION_CONSULT_ALL_PROPERTIES_ENDPOINT' => '/api/consult-all-properties',
        ]);

        $http = new CapturingHttpClient();
        $validator = new ListingPayloadValidator(new SchemaCatalog());
        $api = new ListingsApi($http, $validator, $config);

        $api->create($this->validListingPayload());
        self::assertSame('POST', $http->lastMethod);
        self::assertSame('/api/create', $http->lastPath);

        $api->update($this->validListingPayload());
        self::assertSame('POST', $http->lastMethod);
        self::assertSame('/api/update', $http->lastPath);

        $api->list('client-cookie');
        self::assertSame('GET', $http->lastMethod);
        self::assertSame('/api/consult-all-properties', $http->lastPath);

        $api->get('listing-123');
        self::assertSame('GET', $http->lastMethod);
        self::assertSame('/api/consult-property', $http->lastPath);
        self::assertSame('listing-123', $http->lastOptions['query']['listing_id'] ?? null);
    }

    public function testTasksApiUsesConsultStatusEndpoint(): void
    {
        $config = Config::fromHomlityEnv('token', [
            'ciencuadras_environment' => 'production',
            'CIENCUADRAS_PRODUCTION_CONSULT_STATUS_ENDPOINT' => '/api/consult-status',
        ]);

        $http = new CapturingHttpClient();
        $api = new TasksApi($http, $config);
        $api->get('task-999');

        self::assertSame('GET', $http->lastMethod);
        self::assertSame('/api/consult-status', $http->lastPath);
        self::assertSame('task-999', $http->lastOptions['query']['task_id'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function validListingPayload(): array
    {
        return [
            'listing_id' => 'abc-123',
            'external_code' => 'INT-001',
            'description' => 'Apto',
            'client_id' => 'client-1',
            'offer' => 'sell',
            'property_type' => 'house',
            'price' => 100000,
            'address' => ['address' => 'Dir'],
            'locations' => [
                'location_point' => ['latitude' => 1.1, 'longitude' => 2.2],
                'location_main_id' => 'loc-1',
                'view_map' => 2,
            ],
            'area' => 50,
            'listing_contact' => [
                'emails' => [['email' => 'a@a.com', 'is_main' => true, 'sort_order' => 0]],
                'phones' => [['phone' => '+573001112233', 'sort_order' => 0]],
            ],
        ];
    }
}

final class CapturingHttpClient implements HttpClientInterface
{
    public string $lastMethod = '';
    public string $lastPath = '';
    /** @var array<string, mixed> */
    public array $lastOptions = [];

    public function request(string $method, string $path, array $options = []): ApiResponse
    {
        $this->lastMethod = $method;
        $this->lastPath = $path;
        $this->lastOptions = $options;

        return new ApiResponse(200, [], '{"id":"ok","status":"COMPLETED","results":[]}');
    }
}

