<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Auth\AuthClient;
use Femitz\C6BankPhp\Auth\Certificate;
use Femitz\C6BankPhp\Auth\Credentials;
use Femitz\C6BankPhp\Bolepix\BolepixClient;
use Femitz\C6BankPhp\Config;
use Femitz\C6BankPhp\Environment;
use Femitz\C6BankPhp\PartnerSoftware;
use Femitz\C6BankPhp\Webhook\WebhookClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @return array{cert: string, key: string}
 */
function makeCertificateFiles(): array
{
    $cert = tempnam(sys_get_temp_dir(), 'c6bank-cert');
    $key = tempnam(sys_get_temp_dir(), 'c6bank-key');

    if ($cert === false || $key === false) {
        throw new RuntimeException('Não foi possível criar arquivos temporários para o teste.');
    }

    file_put_contents($cert, 'fake-cert-contents');
    file_put_contents($key, 'fake-key-contents');

    return ['cert' => $cert, 'key' => $key];
}

/**
 * @param  array<int, ResponseInterface|Throwable>  $queue
 * @return array{client: Client, requests: array<int, RequestInterface>}
 */
function makeMockedHttpClient(array $queue): array
{
    $requests = [];

    $mock = new MockHandler($queue);
    $stack = HandlerStack::create($mock);
    $stack->push(function (callable $handler) use (&$requests): callable {
        return function (RequestInterface $request, array $options) use ($handler, &$requests) {
            $requests[] = $request;

            return $handler($request, $options);
        };
    });

    $client = new Client([
        'handler' => $stack,
        'base_uri' => 'https://baas-api-sandbox.c6bank.info',
    ]);

    return ['client' => $client, 'requests' => &$requests];
}

/**
 * @param  array<int, ResponseInterface|Throwable>  $queue
 * @return array{auth: AuthClient, requests: array<int, RequestInterface>}
 */
function makeAuthClient(array $queue): array
{
    $mocked = makeMockedHttpClient($queue);

    $authClient = new AuthClient($mocked['client'], new Credentials('client-id', 'client-secret'));

    return ['auth' => $authClient, 'requests' => &$mocked['requests']];
}

function makeConfig(Environment|string $environment = Environment::Sandbox, int $tokenSafetyMarginSeconds = 30): Config
{
    $files = makeCertificateFiles();

    return new Config(
        credentials: new Credentials('client-id', 'client-secret'),
        certificate: new Certificate($files['cert'], $files['key']),
        partnerSoftware: new PartnerSoftware('Test Suite', '1.0.0'),
        environment: $environment,
        tokenSafetyMarginSeconds: $tokenSafetyMarginSeconds,
    );
}

/**
 * Monta um BolepixClient mockado. A primeira resposta da fila é sempre a
 * autenticação (disparada automaticamente pelo AuthClient), então
 * `$queue` deve conter apenas as respostas para as chamadas ao bolepix,
 * e `requests[0]` sempre será a requisição de auth.
 *
 * @param  array<int, ResponseInterface|Throwable>  $queue
 * @return array{bolepix: BolepixClient, requests: array<int, RequestInterface>}
 */
function makeBolepixClient(array $queue, ?Environment $environment = Environment::Sandbox): array
{
    $mocked = makeMockedHttpClient([
        new Response(200, [], json_encode([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)),
        ...$queue,
    ]);

    $authClient = new AuthClient($mocked['client'], new Credentials('client-id', 'client-secret'));

    $bolepixClient = new BolepixClient(
        httpClient: $mocked['client'],
        authClient: $authClient,
        partnerSoftware: new PartnerSoftware('Test Suite', '1.0.0'),
        environment: $environment,
    );

    return ['bolepix' => $bolepixClient, 'requests' => &$mocked['requests']];
}

/**
 * Monta um WebhookClient mockado. A primeira resposta da fila é sempre a
 * autenticação (disparada automaticamente pelo AuthClient), então
 * `$queue` deve conter apenas as respostas para as chamadas ao webhook,
 * e `requests[0]` sempre será a requisição de auth.
 *
 * @param  array<int, ResponseInterface|Throwable>  $queue
 * @return array{webhook: WebhookClient, requests: array<int, RequestInterface>}
 */
function makeWebhookClient(array $queue): array
{
    $mocked = makeMockedHttpClient([
        new Response(200, [], json_encode([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)),
        ...$queue,
    ]);

    $authClient = new AuthClient($mocked['client'], new Credentials('client-id', 'client-secret'));

    $webhookClient = new WebhookClient(
        httpClient: $mocked['client'],
        authClient: $authClient,
        partnerSoftware: new PartnerSoftware('Test Suite', '1.0.0'),
    );

    return ['webhook' => $webhookClient, 'requests' => &$mocked['requests']];
}
