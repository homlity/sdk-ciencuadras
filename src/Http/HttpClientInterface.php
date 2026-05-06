<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Http;

interface HttpClientInterface
{
    /**
     * @param array{
     *   query?: array<string, scalar|array<scalar>|null>,
     *   headers?: array<string, string>,
     *   json?: mixed,
     *   body?: string
     * } $options
     */
    public function request(string $method, string $path, array $options = []): ApiResponse;
}
