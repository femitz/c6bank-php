<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Auth;

use Femitz\C6BankPhp\Exceptions\AuthenticationFailedException;
use Femitz\C6BankPhp\Exceptions\MalformedResponseException;
use Femitz\C6BankPhp\Exceptions\NetworkException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/**
 * Obtém e mantém em cache (em memória) o access token do C6 Bank via
 * client_credentials.
 */
final class AuthClient
{
    private const string AUTH_PATH = '/v1/auth/';

    private const int DEFAULT_SAFETY_MARGIN_SECONDS = 30;

    private ?AccessToken $cachedToken = null;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly Credentials $credentials,
        private readonly int $safetyMarginSeconds = self::DEFAULT_SAFETY_MARGIN_SECONDS,
    ) {}

    public function getAccessToken(): AccessToken
    {
        if ($this->cachedToken instanceof AccessToken && ! $this->cachedToken->isExpired($this->safetyMarginSeconds)) {
            return $this->cachedToken;
        }

        return $this->cachedToken = $this->authenticate();
    }

    private function authenticate(): AccessToken
    {
        try {
            $response = $this->httpClient->request('POST', self::AUTH_PATH, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'client_id' => $this->credentials->clientId,
                    'client_secret' => $this->credentials->clientSecret,
                    'grant_type' => 'client_credentials',
                ],
            ]);
        } catch (ConnectException $exception) {
            throw NetworkException::fromConnectException($exception);
        } catch (RequestException $exception) {
            throw AuthenticationFailedException::fromRequestException($exception);
        }

        return $this->parseTokenResponse($response);
    }

    private function parseTokenResponse(ResponseInterface $response): AccessToken
    {
        $body = (string) $response->getBody();

        try {
            $decoded = json_decode($body, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw MalformedResponseException::forReason('corpo da resposta não é um JSON válido.');
        }

        if (! is_array($decoded)) {
            throw MalformedResponseException::forReason('corpo da resposta não é um objeto JSON.');
        }

        $token = $decoded['access_token'] ?? null;
        $type = $decoded['token_type'] ?? null;
        $expiresIn = $decoded['expires_in'] ?? null;
        $scope = $decoded['scope'] ?? null;

        if (! is_string($token) || $token === '') {
            throw MalformedResponseException::forReason('campo "access_token" ausente ou inválido.');
        }

        if (! is_string($type) || $type === '') {
            throw MalformedResponseException::forReason('campo "token_type" ausente ou inválido.');
        }

        if (! is_int($expiresIn)) {
            throw MalformedResponseException::forReason('campo "expires_in" ausente ou inválido.');
        }

        if ($scope !== null && ! is_string($scope)) {
            throw MalformedResponseException::forReason('campo "scope" inválido.');
        }

        return AccessToken::fromExpiresIn($token, $type, $expiresIn, $scope);
    }
}
