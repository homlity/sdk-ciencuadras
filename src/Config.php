<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk;

final class Config
{
    public const BASE_URL_PRODUCTION = 'https://kong.ciencuadras.com.co/management/api/1.0';
    public const BASE_URL_QA = 'https://kong-qa.ciencuadras.com.co/management/api/1.0';
    public const BASE_URL_MOCK = 'https://virtserver.swaggerhub.com/Ciencuadras.com.co/Integradores/1.0.0';
    public const BASE_URL_PORTAL_SANDBOX = 'https://dev-ws-api.ciencuadras.com';
    public const BASE_URL_PORTAL_PRODUCTION = 'https://ws-api.ciencuadras.com';

    public const ENDPOINT_LOGIN = '/login';
    public const ENDPOINT_CREATE = '/api/create';
    public const ENDPOINT_UPDATE = '/api/update';
    public const ENDPOINT_CONSULT_STATUS = '/api/consult-status';
    public const ENDPOINT_CONSULT_PROPERTY = '/api/consult-property';
    public const ENDPOINT_CONSULT_ALL_PROPERTIES = '/api/consult-all-properties';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = self::BASE_URL_PRODUCTION,
        private readonly int $timeoutSeconds = 30,
        /** @var array<string, string> */
        private readonly array $endpoints = [],
    ) {
        if ($this->apiKey === '') {
            throw new \InvalidArgumentException('API key is required.');
        }

        if ($this->timeoutSeconds <= 0) {
            throw new \InvalidArgumentException('Timeout must be greater than zero.');
        }
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function baseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    /**
     * @param array<string, string|null> $env
     */
    public static function fromHomlityEnv(
        string $apiKey,
        array $env,
        int $timeoutSeconds = 30
    ): self {
        $environment = strtolower(trim((string) ($env['ciencuadras_environment'] ?? 'production')));
        $isSandbox = in_array($environment, ['sandbox', 'qa', 'development', 'dev'], true);

        $baseUrlKey = $isSandbox
            ? 'CIENCUADRAS_SANDBOX_BASE_URL'
            : 'CIENCUADRAS_PRODUCTION_BASE_URL';

        $baseUrl = (string) ($env[$baseUrlKey] ?? '');
        if ($baseUrl === '') {
            $baseUrl = $isSandbox
                ? self::BASE_URL_PORTAL_SANDBOX
                : self::BASE_URL_PORTAL_PRODUCTION;
        }

        $endpointPrefix = $isSandbox ? 'CIENCUADRAS_SANDBOX_' : 'CIENCUADRAS_PRODUCTION_';
        $endpoints = [
            'login' => $env[$endpointPrefix . 'LOGIN_ENDPOINT'] ?? self::ENDPOINT_LOGIN,
            'create' => $env[$endpointPrefix . 'CREATE_ENDPOINT'] ?? self::ENDPOINT_CREATE,
            'update' => $env[$endpointPrefix . 'UPDATE_ENDPOINT'] ?? self::ENDPOINT_UPDATE,
            'consult_status' => $env[$endpointPrefix . 'CONSULT_STATUS_ENDPOINT'] ?? self::ENDPOINT_CONSULT_STATUS,
            'consult_property' => $env[$endpointPrefix . 'CONSULT_PROPERTY_ENDPOINT'] ?? self::ENDPOINT_CONSULT_PROPERTY,
            'consult_all_properties' => $env[$endpointPrefix . 'CONSULT_ALL_PROPERTIES_ENDPOINT'] ?? self::ENDPOINT_CONSULT_ALL_PROPERTIES,
            'price_caps' => $env[$endpointPrefix . 'PRICE_CAPS_ENDPOINT'] ?? null,
            'area_caps' => $env[$endpointPrefix . 'AREA_CAPS_ENDPOINT'] ?? null,
            'zone_caps' => $env[$endpointPrefix . 'ZONE_CAPS_ENDPOINT'] ?? null,
        ];

        return new self(
            apiKey: $apiKey,
            baseUrl: $baseUrl,
            timeoutSeconds: $timeoutSeconds,
            endpoints: self::sanitizeEndpoints($endpoints),
        );
    }

    public function endpoint(string $name, ?string $default = null): ?string
    {
        return $this->endpoints[$name] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function endpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * @param array<string, string|null> $endpoints
     * @return array<string, string>
     */
    private static function sanitizeEndpoints(array $endpoints): array
    {
        $normalized = [];
        foreach ($endpoints as $name => $value) {
            if (!is_string($value)) {
                continue;
            }
            $value = trim($value);
            if ($value === '') {
                continue;
            }
            if (!str_starts_with($value, '/')
                && !str_starts_with($value, 'http://')
                && !str_starts_with($value, 'https://')) {
                $value = '/' . $value;
            }
            $normalized[$name] = $value;
        }

        return $normalized;
    }
}
