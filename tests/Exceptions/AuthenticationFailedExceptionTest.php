<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Exceptions\AuthenticationFailedException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

it('extracts status code and body from a request exception with a response', function (): void {
    $request = new Request('POST', '/v1/auth/');
    $response = new Response(401, [], 'unauthorized');

    $exception = AuthenticationFailedException::fromRequestException(
        RequestException::create($request, $response),
    );

    expect($exception->statusCode)->toBe(401)
        ->and($exception->responseBody)->toBe('unauthorized')
        ->and($exception->getMessage())->toContain('401');
});

it('handles a request exception without a response', function (): void {
    $request = new Request('POST', '/v1/auth/');

    $exception = AuthenticationFailedException::fromRequestException(
        RequestException::create($request),
    );

    expect($exception->statusCode)->toBeNull()
        ->and($exception->responseBody)->toBeNull();
});
