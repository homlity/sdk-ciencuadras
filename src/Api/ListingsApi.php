<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Api;

use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\Data\ListingSnapshot;
use Ciencuadras\Sdk\Schema\ListingPayloadValidator;

final class ListingsApi extends BaseApi
{
    public function __construct(
        \Ciencuadras\Sdk\Http\HttpClientInterface $httpClient,
        private readonly ListingPayloadValidator $validator,
        private readonly ?Config $config = null,
    ) {
        parent::__construct($httpClient);
    }

    /**
     * GET /listing
     *
     * @param array<string, scalar|array<scalar>|null> $query
     */
    public function list(string $clientCookie, array $query = []): mixed
    {
        return $this->send('GET', $this->path('consult_all_properties', '/listing'), [
            'query' => $query,
            'headers' => [
                'Cookie' => $clientCookie,
            ],
        ]);
    }

    /**
     * GET /listing/{listing_id}
     *
     * Devuelve el array crudo de la respuesta JSON.
     * Para una versión tipada usa {@see getSnapshot()}.
     */
    public function get(string $listingId): mixed
    {
        $path = $this->path('consult_property', '/listing/' . $this->encodePath($listingId));
        $options = [];

        // Para endpoint estilo portal (/api/consult-property) enviamos listing_id en query.
        if ($path === ($this->config?->endpoint('consult_property') ?? '')) {
            $options['query'] = ['listing_id' => $listingId];
        }

        return $this->send('GET', $path, $options);
    }

    /**
     * GET /listing/{listing_id}
     *
     * Igual que {@see get()} pero envuelve la respuesta en un {@see ListingSnapshot}
     * con helpers como isActive(), isDeleted(), isPending().
     */
    public function getSnapshot(string $listingId): ListingSnapshot
    {
        $data = $this->get($listingId);
        return ListingSnapshot::fromArray(is_array($data) ? $data : []);
    }

    /**
     * Busca un inmueble por su `external_code` dentro de los resultados de
     * GET /listing para el cliente indicado.
     *
     * Devuelve `null` si no se encuentra ningún resultado.
     * Si la respuesta está paginada solo busca en la primera página; para
     * búsquedas en colecciones grandes pasa `['page' => N]` en `$extraQuery`.
     *
     * @param array<string, scalar|array<scalar>|null> $extraQuery Filtros adicionales.
     */
    public function findByExternalCode(
        string $clientCookie,
        string $externalCode,
        array $extraQuery = [],
    ): ?ListingSnapshot {
        $data = $this->list($clientCookie, array_merge($extraQuery, ['external_code' => $externalCode]));

        // La respuesta de list() es { next, previous, count, results: [...] }
        $results = is_array($data) ? ($data['results'] ?? $data) : [];

        if (!is_array($results) || $results === []) {
            return null;
        }

        foreach ($results as $item) {
            if (!is_array($item)) {
                continue;
            }
            // El filtro API ya debería haber retornado solo coincidencias,
            // pero verificamos igual para evitar falsos positivos.
            $code = isset($item['external_code']) ? (string) $item['external_code'] : null;
            if ($code === $externalCode) {
                return ListingSnapshot::fromArray($item);
            }
        }

        // Si el API no filtra por external_code en query params, devolvemos el primero.
        $first = reset($results);
        return is_array($first) ? ListingSnapshot::fromArray($first) : null;
    }

    /**
     * POST /listing
     *
     * @param array<int|string, mixed> $listings Single listing object or list of listing objects.
     */
    public function create(array $listings): mixed
    {
        $batch = $this->normalizeBatchPayload($listings);
        $this->validator->validateCreatePayload($batch);

        return $this->send('POST', $this->path('create', '/listing'), ['json' => $batch]);
    }

    /**
     * PATCH /listing
     *
     * @param array<int|string, mixed> $listings Single listing object or list of listing objects.
     */
    public function update(array $listings): mixed
    {
        $batch = $this->normalizeBatchPayload($listings);
        $this->validator->validateUpdatePayload($batch);

        $updatePath = $this->path('update', '/listing');
        $method = $updatePath === '/listing' ? 'PATCH' : 'POST';

        return $this->send($method, $updatePath, ['json' => $batch]);
    }

    /**
     * PATCH /listing/status
     *
     * @param array<int|string, mixed> $statuses Single status object or list of status objects.
     */
    public function updateStatus(array $statuses): mixed
    {
        $batch = $this->normalizeBatchPayload($statuses);
        $this->validator->validateStatusPayload($batch);

        $statusPath = $this->path('update', '/listing/status');
        $method = $statusPath === '/listing/status' ? 'PATCH' : 'POST';

        return $this->send($method, $statusPath, ['json' => $batch]);
    }

    /**
     * POST /validate-listing
     *
     * @param array<string, mixed> $payload
     */
    public function validate(array $payload): mixed
    {
        if (!\array_key_exists('client_id', $payload)) {
            throw new \InvalidArgumentException('Missing required field `client_id` for validate-listing payload.');
        }

        return $this->send('POST', '/validate-listing', ['json' => $payload]);
    }

    private function path(string $name, string $default): string
    {
        return $this->config?->endpoint($name, $default) ?? $default;
    }
}
