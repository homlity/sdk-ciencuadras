<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Exception;

use Ciencuadras\Sdk\Http\ApiResponse;

class ApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?ApiResponse $response = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function fromResponse(string $method, string $path, ApiResponse $response): self
    {
        $status = $response->statusCode();
        $body = $response->body();
        $shortBody = \substr($body, 0, 500);

        return new self(
            \sprintf('API request failed [%s %s] with status %d. Response: %s', $method, $path, $status, $shortBody),
            $response
        );
    }

    public function response(): ?ApiResponse
    {
        return $this->response;
    }

    // ---------------------------------------------------------------
    // Accesores de diagnóstico
    // ---------------------------------------------------------------

    /**
     * HTTP status code de la respuesta, o `null` si no hay respuesta.
     */
    public function statusCode(): ?int
    {
        return $this->response?->statusCode();
    }

    /**
     * Cuerpo JSON decodificado, o `null` si no hay respuesta o el cuerpo
     * no es JSON válido.
     */
    public function json(): mixed
    {
        return $this->response?->json();
    }

    /**
     * Tracking/request id de los headers de la respuesta.
     * Revisa `x-tracking-id`, `x-request-id` y `x-correlation-id`.
     */
    public function trackingId(): ?string
    {
        $headers = $this->response?->headers() ?? [];

        foreach (['x-tracking-id', 'x-request-id', 'x-correlation-id'] as $name) {
            $value = $headers[$name] ?? $headers[strtoupper($name)] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Código de error canónico devuelto por la API
     * (busca `defaultCode`, `code` y `error_code` en el JSON).
     */
    public function defaultCode(): ?string
    {
        $json = $this->json();
        if (!is_array($json)) {
            return null;
        }

        foreach (['defaultCode', 'code', 'error_code', 'errorCode'] as $key) {
            if (isset($json[$key]) && (is_string($json[$key]) || is_int($json[$key]))) {
                $value = (string) $json[$key];
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Primer mensaje de error legible del cuerpo JSON.
     *
     * Intenta los formatos más comunes que usa Ciencuadras:
     * `message`, `errors[0]`, `errors[0].message`, `detail`.
     */
    public function firstErrorMessage(): ?string
    {
        $json = $this->json();
        if (!is_array($json)) {
            return null;
        }

        // Formato plano: { "message": "..." }
        if (isset($json['message']) && is_string($json['message']) && $json['message'] !== '') {
            return $json['message'];
        }

        // Formato plano: { "detail": "..." }
        if (isset($json['detail']) && is_string($json['detail']) && $json['detail'] !== '') {
            return $json['detail'];
        }

        // Array de errores: { "errors": ["...", ...] } o { "errors": [{"message": "..."}] }
        if (isset($json['errors']) && is_array($json['errors']) && $json['errors'] !== []) {
            $first = reset($json['errors']);

            if (is_string($first) && $first !== '') {
                return $first;
            }

            if (is_array($first)) {
                foreach (['message', 'detail', 'msg'] as $key) {
                    if (isset($first[$key]) && is_string($first[$key]) && $first[$key] !== '') {
                        return $first[$key];
                    }
                }
            }
        }

        return null;
    }
}
