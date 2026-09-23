<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Auth\AuthClient;
use Femitz\C6BankPhp\Auth\Certificate;
use Femitz\C6BankPhp\Auth\Credentials;
use Femitz\C6BankPhp\Config;
use Femitz\C6BankPhp\Environment;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
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
        environment: $environment,
        tokenSafetyMarginSeconds: $tokenSafetyMarginSeconds,
    );
}
