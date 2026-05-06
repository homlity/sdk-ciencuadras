<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Api;

use Ciencuadras\Sdk\Exception\ApiException;
use Ciencuadras\Sdk\Http\HttpClientInterface;

abstract class BaseApi
{
    public function __construct(protected readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * @param array{
     *   query?: array<string, scalar|array<scalar>|null>,
     *   headers?: array<string, string>,
     *   json?: mixed,
     *   body?: string
     * } $options
     */
    protected function send(string $method, string $path, array $options = []): mixed
    {
        $response = $this->httpClient->request($method, $path, $options);

        if (!$response->isSuccessful()) {
            throw ApiException::fromResponse(strtoupper($method), $path, $response);
        }

        $json = $response->json();
        return $json ?? $response->body();
    }

    protected function encodePath(string $value): string
    {
        return rawurlencode($value);
    }

    /**
     * @param array<int|string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeBatchPayload(array $payload): array
    {
        if ($payload === []) {
            return [];
        }

        if (array_is_list($payload)) {
            /** @var array<int, array<string, mixed>> $payload */
            return $payload;
        }

        /** @var array<string, mixed> $payload */
        return [$payload];
    }
}
