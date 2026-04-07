<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-type ResponseData array<string, mixed>
 * @phpstan-type ResponseHeaders array<string, string|string[]>
 * @phpstan-type ErrorFields list<array{field: string, message: string}>
 */
final class JsonResponder
{
    /**
     * @param ResponseData    $data
     * @param ResponseHeaders $headers
     */
    public function success(
        array $data = [],
        ?string $message = null,
        int $status = Response::HTTP_OK,
        array $headers = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if (null !== $message) {
            $payload['message'] = $message;
        }

        return new JsonResponse($payload, $status, $headers);
    }

    /**
     * @param ResponseData    $data
     * @param ResponseHeaders $headers
     */
    public function created(
        array $data = [],
        ?string $message = null,
        array $headers = [],
    ): JsonResponse {
        return $this->success(
            data: $data,
            message: $message,
            status: Response::HTTP_CREATED,
            headers: $headers,
        );
    }

    /**
     * @param ErrorFields     $fields
     * @param ResponseHeaders $headers
     */
    public function error(
        string $code,
        string $message,
        int $status,
        array $fields = [],
        array $headers = [],
    ): JsonResponse {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ([] !== $fields) {
            $error['fields'] = $fields;
        }

        return new JsonResponse([
            'success' => false,
            'error' => $error,
        ], $status, $headers);
    }

    /**
     * @param ResponseHeaders $headers
     */
    public function noContent(array $headers = []): Response
    {
        return new Response(null, Response::HTTP_NO_CONTENT, $headers);
    }
}
