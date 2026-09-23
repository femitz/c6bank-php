<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Auth\AuthClient;
use Femitz\C6BankPhp\Auth\Credentials;
use Femitz\C6BankPhp\Exceptions\AuthenticationFailedException;
use Femitz\C6BankPhp\Exceptions\MalformedResponseException;
use Femitz\C6BankPhp\Exceptions\NetworkException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

it('authenticates and returns the access token', function (): void {
    $mocked = makeAuthClient([
        new Response(200, [], json_encode([
            'access_token' => 'abc123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'pix.read',
        ], JSON_THROW_ON_ERROR)),
    ]);

    $token = $mocked['auth']->getAccessToken();

    expect($token->token)->toBe('abc123')
        ->and($token->type)->toBe('Bearer')
        ->and($token->scope)->toBe('pix.read')
        ->and($mocked['requests'])->toHaveCount(1);

    $request = $mocked['requests'][0];

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toContain('/v1/auth/')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/x-www-form-urlencoded')
        ->and((string) $request->getBody())->toContain('grant_type=client_credentials')
        ->and((string) $request->getBody())->toContain('client_id=client-id')
        ->and((string) $request->getBody())->toContain('client_secret=client-secret');
});

it('returns the token without scope when absent', function (): void {
    $mocked = makeAuthClient([
        new Response(200, [], json_encode([
            'access_token' => 'abc123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)),
    ]);

    $token = $mocked['auth']->getAccessToken();

    expect($token->scope)->toBeNull();
});

it('caches the token in memory between calls', function (): void {
    $mocked = makeAuthClient([
        new Response(200, [], json_encode([
            'access_token' => 'abc123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)),
    ]);

    $first = $mocked['auth']->getAccessToken();
    $second = $mocked['auth']->getAccessToken();

    expect($second->token)->toBe($first->token)
        ->and($mocked['requests'])->toHaveCount(1);
});

it('re-authenticates once the cached token expires', function (): void {
    $mocked = makeAuthClient([
        new Response(200, [], json_encode([
            'access_token' => 'first-token',
            'token_type' => 'Bearer',
            'expires_in' => 0,
        ], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode([
            'access_token' => 'second-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)),
    ]);

    $first = $mocked['auth']->getAccessToken();
    $second = $mocked['auth']->getAccessToken();

    expect($first->token)->toBe('first-token')
        ->and($second->token)->toBe('second-token')
        ->and($mocked['requests'])->toHaveCount(2);
});

it('throws AuthenticationFailedException on http error responses', function (int $status): void {
    $mocked = makeAuthClient([
        new Response($status, [], json_encode(['message' => 'invalid credentials'], JSON_THROW_ON_ERROR)),
    ]);

    try {
        $mocked['auth']->getAccessToken();
    } catch (AuthenticationFailedException $authenticationFailedException) {
        expect($authenticationFailedException->statusCode)->toBe($status)
            ->and($authenticationFailedException->responseBody)->toContain('invalid credentials');

        return;
    }

    $this->fail('Expected AuthenticationFailedException was not thrown.');
})->with([401, 403, 500]);

it('throws NetworkException on connection failures', function (): void {
    $mocked = makeMockedHttpClient([
        new ConnectException('Connection refused', new Request('POST', '/v1/auth/')),
    ]);

    $authClient = new AuthClient($mocked['client'], new Credentials('client-id', 'client-secret'));

    $authClient->getAccessToken();
})->throws(NetworkException::class);

it('throws MalformedResponseException on invalid json', function (): void {
    $mocked = makeAuthClient([
        new Response(200, [], 'not-json'),
    ]);

    $mocked['auth']->getAccessToken();
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException on a non-object json body', function (): void {
    $mocked = makeAuthClient([
        new Response(200, [], '"5"'),
    ]);

    $mocked['auth']->getAccessToken();
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when access_token is missing', function (): void {
    $mocked = makeAuthClient([
        new Response(200, [], json_encode([
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)),
    ]);

    $mocked['auth']->getAccessToken();
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when token_type is missing', function (): void {
    $mocked = makeAuthClient([
        new Response(200, [], json_encode([
            'access_token' => 'abc123',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)),
    ]);

    $mocked['auth']->getAccessToken();
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when expires_in is missing', function (): void {
    $mocked = makeAuthClient([
        new Response(200, [], json_encode([
            'access_token' => 'abc123',
            'token_type' => 'Bearer',
        ], JSON_THROW_ON_ERROR)),
    ]);

    $mocked['auth']->getAccessToken();
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when scope is not a string', function (): void {
    $mocked = makeAuthClient([
        new Response(200, [], json_encode([
            'access_token' => 'abc123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 123,
        ], JSON_THROW_ON_ERROR)),
    ]);

    $mocked['auth']->getAccessToken();
})->throws(MalformedResponseException::class);
