<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Api;

final class CategoriesApi extends BaseApi
{
    /**
     * GET /category
     *
     * @param array<string, scalar|array<scalar>|null> $query
     */
    public function list(array $query = []): mixed
    {
        return $this->send('GET', '/category', ['query' => $query]);
    }
}
