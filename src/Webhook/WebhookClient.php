<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Webhook;

use Femitz\C6BankPhp\Auth\AuthClient;
use Femitz\C6BankPhp\Exceptions\ApiException;
use Femitz\C6BankPhp\Exceptions\MalformedResponseException;
use Femitz\C6BankPhp\Exceptions\NetworkException;
use Femitz\C6BankPhp\PartnerSoftware;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final readonly class WebhookClient
{
    private const string REGISTER_PATH = '/v1/webhooks/';

    public function __construct(
        private ClientInterface $httpClient,
        private AuthClient $authClient,
        private PartnerSoftware $partnerSoftware,
    ) {}

    public function register(RegisterWebhookRequest $request): Webhook
    {
        try {
            $response = $this->httpClient->request('POST', self::REGISTER_PATH, [
                'headers' => [
                    'Authorization' => $this->authClient->getAccessToken()->authorizationHeader(),
                    'partner-software-name' => $this->partnerSoftware->name,
                    'partner-software-version' => $this->partnerSoftware->version,
                    'Content-Type' => 'application/json',
                ],
                'json' => $request->toArray(),
            ]);
        } catch (ConnectException $exception) {
            throw NetworkException::fromConnectException($exception);
        } catch (RequestException $exception) {
            throw ApiException::fromRequestException($exception);
        }

        return $this->parseWebhookResponse($response);
    }

    /**
     * Consulta o webhook registrado para o serviço informado.
     */
    public function get(WebhookService $service): Webhook
    {
        try {
            $response = $this->httpClient->request('GET', self::REGISTER_PATH, [
                'headers' => [
                    'Authorization' => $this->authClient->getAccessToken()->authorizationHeader(),
                    'partner-software-name' => $this->partnerSoftware->name,
                    'partner-software-version' => $this->partnerSoftware->version,
                ],
                'query' => [
                    'service' => $service->value,
                ],
            ]);
        } catch (ConnectException $exception) {
            throw NetworkException::fromConnectException($exception);
        } catch (RequestException $exception) {
            throw ApiException::fromRequestException($exception);
        }

        return $this->parseWebhookResponse($response);
    }

    /**
     * Remove o webhook registrado para o serviço informado.
     */
    public function delete(WebhookService $service): void
    {
        try {
            $this->httpClient->request('DELETE', self::REGISTER_PATH, [
                'headers' => [
                    'Authorization' => $this->authClient->getAccessToken()->authorizationHeader(),
                    'partner-software-name' => $this->partnerSoftware->name,
                    'partner-software-version' => $this->partnerSoftware->version,
                ],
                'query' => [
                    'service' => $service->value,
                ],
            ]);
        } catch (ConnectException $exception) {
            throw NetworkException::fromConnectException($exception);
        } catch (RequestException $exception) {
            throw ApiException::fromRequestException($exception);
        }
    }

    private function parseWebhookResponse(ResponseInterface $response): Webhook
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

        $service = $decoded['service'] ?? null;
        $clientId = $decoded['client_id'] ?? null;
        $url = $decoded['url'] ?? null;
        $createdAt = $decoded['created_at'] ?? null;

        if (! is_string($service) || $service === '') {
            throw MalformedResponseException::forReason('campo "service" ausente ou inválido.');
        }

        $webhookService = WebhookService::tryFrom($service);

        if (! $webhookService instanceof WebhookService) {
            throw MalformedResponseException::forReason(sprintf('valor "%s" desconhecido para o campo "service".', $service));
        }

        if (! is_string($clientId) || $clientId === '') {
            throw MalformedResponseException::forReason('campo "client_id" ausente ou inválido.');
        }

        if (! is_string($url) || $url === '') {
            throw MalformedResponseException::forReason('campo "url" ausente ou inválido.');
        }

        if (! is_string($createdAt) || $createdAt === '') {
            throw MalformedResponseException::forReason('campo "created_at" ausente ou inválido.');
        }

        return new Webhook(
            service: $webhookService,
            clientId: $clientId,
            url: $url,
            createdAt: $createdAt,
        );
    }
}
