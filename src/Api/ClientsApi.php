<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Api;

final class ClientsApi extends BaseApi
{
    /**
     * GET /client/
     */
    public function all(): mixed
    {
        return $this->send('GET', '/client/');
    }

    /**
     * GET /client/{client_id}
     */
    public function get(string $clientId): mixed
    {
        return $this->send('GET', '/client/' . $this->encodePath($clientId));
    }

    /**
     * GET /client/{client_id}/agent
     */
    public function agents(string $clientId): mixed
    {
        return $this->send('GET', '/client/' . $this->encodePath($clientId) . '/agent');
    }

    /**
     * Retorna el `id` del único agente asociado a un cliente.
     *
     * Si el cliente tiene exactamente un agente, devuelve su `id` (entero).
     * Si tiene cero o más de uno lanza una excepción para que el llamador
     * especifique el agente explícitamente.
     *
     * @throws \RuntimeException si el cliente no tiene agentes o tiene más de uno.
     */
    public function resolveSingleAgentId(string $clientId): int
    {
        $data = $this->agents($clientId);

        // La respuesta es un array directo de agentes (AgentResponse200 schema)
        $agents = is_array($data) ? array_values($data) : [];

        if (count($agents) === 0) {
            throw new \RuntimeException(sprintf(
                'Client "%s" has no agents. Assign at least one agent before publishing.',
                $clientId,
            ));
        }

        if (count($agents) > 1) {
            $ids = array_map(static fn ($a) => is_array($a) ? ($a['id'] ?? '?') : '?', $agents);
            throw new \RuntimeException(sprintf(
                'Client "%s" has %d agents (%s). Provide the agent id explicitly.',
                $clientId,
                count($agents),
                implode(', ', $ids),
            ));
        }

        $agent = $agents[0];
        $id = is_array($agent) ? ($agent['id'] ?? null) : null;

        if ($id === null) {
            throw new \RuntimeException(sprintf(
                'Agent response for client "%s" is missing the `id` field.',
                $clientId,
            ));
        }

        return (int) $id;
    }
}
