<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp;

use Femitz\C6BankPhp\Auth\AccessToken;
use Femitz\C6BankPhp\Auth\AuthClient;
use Femitz\C6BankPhp\Bolepix\BolepixClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;

/**
 * Fachada principal do pacote: monta o client HTTP configurado (mTLS) e
 * expõe os recursos da API do C6 Bank.
 */
final readonly class Client
{
    private ClientInterface $httpClient;

    private AuthClient $auth;

    private BolepixClient $bolepix;

    public function __construct(
        private Config $config,
        ?ClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new GuzzleClient([
            'base_uri' => $this->config->baseUrl,
            'cert' => $this->config->certificate->toCertOption(),
            'ssl_key' => $this->config->certificate->toSslKeyOption(),
        ]);

        $this->auth = new AuthClient(
            httpClient: $this->httpClient,
            credentials: $this->config->credentials,
            safetyMarginSeconds: $this->config->tokenSafetyMarginSeconds,
        );

        $this->bolepix = new BolepixClient(
            httpClient: $this->httpClient,
            authClient: $this->auth,
            partnerSoftware: $this->config->partnerSoftware,
            environment: $this->config->environment,
        );
    }

    public function auth(): AuthClient
    {
        return $this->auth;
    }

    public function bolepix(): BolepixClient
    {
        return $this->bolepix;
    }

    public function getAccessToken(): AccessToken
    {
        return $this->auth->getAccessToken();
    }

    public function httpClient(): ClientInterface
    {
        return $this->httpClient;
    }
}
