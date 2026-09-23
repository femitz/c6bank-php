<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;

it('delegates getAccessToken to the injected http client', function (): void {
    $mocked = makeMockedHttpClient([
        new Response(200, [], json_encode([
            'access_token' => 'abc123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)),
    ]);

    $client = new Client(makeConfig(), $mocked['client']);

    $token = $client->getAccessToken();

    expect($token->token)->toBe('abc123')
        ->and($mocked['requests'])->toHaveCount(1);
});

it('always exposes the same AuthClient instance', function (): void {
    $mocked = makeMockedHttpClient([]);

    $client = new Client(makeConfig(), $mocked['client']);

    expect($client->auth())->toBe($client->auth());
});

it('exposes the configured http client', function (): void {
    $mocked = makeMockedHttpClient([]);

    $client = new Client(makeConfig(), $mocked['client']);

    expect($client->httpClient())->toBe($mocked['client']);
});

it('builds a default guzzle client with mTLS options when none is injected', function (): void {
    $client = new Client(makeConfig());

    expect($client->httpClient())->toBeInstanceOf(GuzzleClient::class);
});
