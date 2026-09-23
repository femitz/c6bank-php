<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Exceptions\ApiException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

it('extracts status code and body from a request exception with a response', function (): void {
    $request = new Request('POST', '/v2/bank_slips');
    $response = new Response(422, [], 'validation error');

    $exception = ApiException::fromRequestException(
        RequestException::create($request, $response),
    );

    expect($exception->statusCode)->toBe(422)
        ->and($exception->responseBody)->toBe('validation error')
        ->and($exception->getMessage())->toContain('422');
});

it('handles a request exception without a response', function (): void {
    $request = new Request('POST', '/v2/bank_slips');

    $exception = ApiException::fromRequestException(
        RequestException::create($request),
    );

    expect($exception->statusCode)->toBeNull()
        ->and($exception->responseBody)->toBeNull();
});
