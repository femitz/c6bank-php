<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Auth\AuthClient;
use Femitz\C6BankPhp\Auth\Credentials;
use Femitz\C6BankPhp\Exceptions\ApiException;
use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;
use Femitz\C6BankPhp\Exceptions\MalformedResponseException;
use Femitz\C6BankPhp\Exceptions\NetworkException;
use Femitz\C6BankPhp\PartnerSoftware;
use Femitz\C6BankPhp\Webhook\RegisterWebhookRequest;
use Femitz\C6BankPhp\Webhook\WebhookClient;
use Femitz\C6BankPhp\Webhook\WebhookService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * @return array<array-key, mixed>
 */
function decodedWebhookRequestBody(RequestInterface $request): array
{
    $body = json_decode((string) $request->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($body)) {
        throw new RuntimeException('Expected the request body to decode to an array.');
    }

    return $body;
}

/**
 * @return array<string, mixed>
 */
function webhookResponseBody(): array
{
    return [
        'service' => 'BANK_SLIP',
        'client_id' => '6abeb8cd-3dda-4f3a-b038-3b55c9d87d6a',
        'url' => 'https://www.meuendereco.com.br/webhook/xpto',
        'created_at' => '2025-11-26T18:02:23.301232079Z',
    ];
}

it('registers a webhook and parses the response', function (): void {
    $mocked = makeWebhookClient([
        new Response(200, [], json_encode(webhookResponseBody(), JSON_THROW_ON_ERROR)),
    ]);

    $webhook = $mocked['webhook']->register(new RegisterWebhookRequest(
        url: 'https://www.meuendereco.com.br/webhook/xpto',
    ));

    expect($webhook->service)->toBe(WebhookService::BankSlip)
        ->and($webhook->clientId)->toBe('6abeb8cd-3dda-4f3a-b038-3b55c9d87d6a')
        ->and($webhook->url)->toBe('https://www.meuendereco.com.br/webhook/xpto')
        ->and($webhook->createdAt)->toBe('2025-11-26T18:02:23.301232079Z');

    $request = $mocked['requests'][1];

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toContain('/v1/webhooks/')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer test-token')
        ->and($request->getHeaderLine('partner-software-name'))->toBe('Test Suite')
        ->and($request->getHeaderLine('partner-software-version'))->toBe('1.0.0')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(decodedWebhookRequestBody($request))->toBe([
            'url' => 'https://www.meuendereco.com.br/webhook/xpto',
            'service' => 'BANK_SLIP',
        ]);
});

it('registers a webhook for a given service', function (WebhookService $service): void {
    $mocked = makeWebhookClient([
        new Response(200, [], json_encode([
            ...webhookResponseBody(),
            'service' => $service->value,
        ], JSON_THROW_ON_ERROR)),
    ]);

    $webhook = $mocked['webhook']->register(new RegisterWebhookRequest(
        url: 'https://www.meuendereco.com.br/webhook/xpto',
        service: $service,
    ));

    expect($webhook->service)->toBe($service);

    $request = $mocked['requests'][1];

    expect(decodedWebhookRequestBody($request)['service'])->toBe($service->value);
})->with([WebhookService::BankSlip, WebhookService::BankSlipPix, WebhookService::Checkout]);

it('fetches a registered webhook and parses the response', function (): void {
    $mocked = makeWebhookClient([
        new Response(200, [], json_encode(webhookResponseBody(), JSON_THROW_ON_ERROR)),
    ]);

    $webhook = $mocked['webhook']->get(WebhookService::BankSlip);

    expect($webhook->service)->toBe(WebhookService::BankSlip)
        ->and($webhook->clientId)->toBe('6abeb8cd-3dda-4f3a-b038-3b55c9d87d6a')
        ->and($webhook->url)->toBe('https://www.meuendereco.com.br/webhook/xpto')
        ->and($webhook->createdAt)->toBe('2025-11-26T18:02:23.301232079Z');

    $request = $mocked['requests'][1];

    expect($request->getMethod())->toBe('GET')
        ->and((string) $request->getUri())->toContain('/v1/webhooks/')
        ->and((string) $request->getUri())->toContain('service=BANK_SLIP')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer test-token')
        ->and($request->getHeaderLine('partner-software-name'))->toBe('Test Suite')
        ->and($request->getHeaderLine('partner-software-version'))->toBe('1.0.0');
});

it('throws ApiException when fetching a webhook returns an http error', function (): void {
    $mocked = makeWebhookClient([
        new Response(404, [], json_encode([
            'type' => 'https://developers.c6bank.com.br/v1/error/not_found',
            'title' => 'Webhook não encontrado.',
            'status' => 404,
        ], JSON_THROW_ON_ERROR)),
    ]);

    try {
        $mocked['webhook']->get(WebhookService::BankSlip);
    } catch (ApiException $apiException) {
        expect($apiException->statusCode)->toBe(404);

        return;
    }

    $this->fail('Expected ApiException was not thrown.');
});

it('throws NetworkException when fetching a webhook fails to connect', function (): void {
    $mocked = makeMockedHttpClient([
        new Response(200, [], json_encode([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)),
        new ConnectException('Connection refused', new Request('GET', '/v1/webhooks/')),
    ]);

    $authClient = new AuthClient($mocked['client'], new Credentials('client-id', 'client-secret'));
    $webhookClient = new WebhookClient($mocked['client'], $authClient, new PartnerSoftware('Test Suite', '1.0.0'));

    $webhookClient->get(WebhookService::BankSlip);
})->throws(NetworkException::class);

it('rejects an empty url when registering a webhook', function (): void {
    new RegisterWebhookRequest(url: '');
})->throws(InvalidConfigurationException::class);

it('rejects an invalid url when registering a webhook', function (): void {
    new RegisterWebhookRequest(url: 'not-a-url');
})->throws(InvalidConfigurationException::class);

it('throws ApiException when registering a webhook returns an http error', function (int $status): void {
    $mocked = makeWebhookClient([
        new Response($status, [], json_encode([
            'type' => 'https://developers.c6bank.com.br/v1/error/validation',
            'title' => 'Requisição inválida.',
            'status' => $status,
            'detail' => 'campo obrigatorio ausente',
        ], JSON_THROW_ON_ERROR)),
    ]);

    try {
        $mocked['webhook']->register(new RegisterWebhookRequest(url: 'https://www.meuendereco.com.br/webhook/xpto'));
    } catch (ApiException $apiException) {
        expect($apiException->statusCode)->toBe($status)
            ->and($apiException->responseBody)->toContain('campo obrigatorio ausente');

        return;
    }

    $this->fail('Expected ApiException was not thrown.');
})->with([400, 401, 422, 500]);

it('throws NetworkException when registering a webhook fails to connect', function (): void {
    $mocked = makeMockedHttpClient([
        new Response(200, [], json_encode([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)),
        new ConnectException('Connection refused', new Request('POST', '/v1/webhooks/')),
    ]);

    $authClient = new AuthClient($mocked['client'], new Credentials('client-id', 'client-secret'));
    $webhookClient = new WebhookClient($mocked['client'], $authClient, new PartnerSoftware('Test Suite', '1.0.0'));

    $webhookClient->register(new RegisterWebhookRequest(url: 'https://www.meuendereco.com.br/webhook/xpto'));
})->throws(NetworkException::class);

it('throws MalformedResponseException on invalid json', function (): void {
    $mocked = makeWebhookClient([
        new Response(200, [], 'not-json'),
    ]);

    $mocked['webhook']->register(new RegisterWebhookRequest(url: 'https://www.meuendereco.com.br/webhook/xpto'));
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException on a non-object json body', function (): void {
    $mocked = makeWebhookClient([
        new Response(200, [], '"5"'),
    ]);

    $mocked['webhook']->register(new RegisterWebhookRequest(url: 'https://www.meuendereco.com.br/webhook/xpto'));
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when a top-level field is missing', function (string $field): void {
    $body = webhookResponseBody();
    unset($body[$field]);

    $mocked = makeWebhookClient([
        new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR)),
    ]);

    $mocked['webhook']->register(new RegisterWebhookRequest(url: 'https://www.meuendereco.com.br/webhook/xpto'));
})->throws(MalformedResponseException::class)->with(['service', 'client_id', 'url', 'created_at']);

it('throws MalformedResponseException when the service field has an unknown value', function (): void {
    $body = webhookResponseBody();
    $body['service'] = 'UNKNOWN';

    $mocked = makeWebhookClient([
        new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR)),
    ]);

    $mocked['webhook']->register(new RegisterWebhookRequest(url: 'https://www.meuendereco.com.br/webhook/xpto'));
})->throws(MalformedResponseException::class);
