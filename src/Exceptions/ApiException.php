<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Exceptions;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

final class ApiException extends C6BankException
{
    private function __construct(
        string $message,
        public readonly ?int $statusCode,
        public readonly ?string $responseBody,
        ?RequestException $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public static function fromRequestException(RequestException $exception): self
    {
        $response = $exception->getResponse();

        if (! $response instanceof ResponseInterface) {
            return new self(
                message: sprintf('Falha na requisição ao C6 Bank: %s', $exception->getMessage()),
                statusCode: null,
                responseBody: null,
                previous: $exception,
            );
        }

        $statusCode = $response->getStatusCode();
        $responseBody = (string) $response->getBody();

        return new self(
            message: sprintf('Falha na requisição ao C6 Bank (HTTP %d): %s', $statusCode, $responseBody),
            statusCode: $statusCode,
            responseBody: $responseBody,
            previous: $exception,
        );
    }
}
