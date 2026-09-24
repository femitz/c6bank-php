<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

use Femitz\C6BankPhp\Auth\AuthClient;
use Femitz\C6BankPhp\Environment;
use Femitz\C6BankPhp\Exceptions\ApiException;
use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;
use Femitz\C6BankPhp\Exceptions\MalformedResponseException;
use Femitz\C6BankPhp\Exceptions\NetworkException;
use Femitz\C6BankPhp\PartnerSoftware;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

final readonly class BolepixClient
{
    private const string CREATE_PATH = '/v2/bank_slips';

    private const string RESOURCE_PATH = '/v2/bank_slips/%s';

    private const string PDF_PATH = '/v2/bank_slips/%s/pdf';

    private const string CANCEL_PATH = '/v2/bank_slips/%s/cancel';

    /**
     * Carteira de cobrança (`billing_scheme`) padrão por ambiente, usada
     * quando o chamador não informa uma explicitamente via
     * `BankSlipOptions::$billingScheme`. Não se aplica quando `Config` usa
     * uma URL de ambiente customizada (environment nulo).
     */
    private const string SANDBOX_BILLING_SCHEME = '21';

    private const string PRODUCTION_BILLING_SCHEME = '15';

    public function __construct(
        private ClientInterface $httpClient,
        private AuthClient $authClient,
        private PartnerSoftware $partnerSoftware,
        private ?Environment $environment = null,
    ) {}

    public function create(CreateBolepixRequest $request): Bolepix
    {
        $payload = $request->toArray();

        $defaultBillingScheme = $this->defaultBillingScheme();

        if ($defaultBillingScheme !== null) {
            $payload = $this->applyDefaultBillingScheme($payload, $defaultBillingScheme);
        }

        try {
            $response = $this->httpClient->request('POST', self::CREATE_PATH, [
                'headers' => [
                    'Authorization' => $this->authClient->getAccessToken()->authorizationHeader(),
                    'partner-software-name' => $this->partnerSoftware->name,
                    'partner-software-version' => $this->partnerSoftware->version,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (ConnectException $exception) {
            throw NetworkException::fromConnectException($exception);
        } catch (RequestException $exception) {
            throw ApiException::fromRequestException($exception);
        }

        return $this->parseBolepixResponse($response);
    }

    /**
     * Consulta um bolepix já emitido a partir do `external_reference_id`
     * informado na emissão.
     */
    public function get(string $externalReferenceId): Bolepix
    {
        if (trim($externalReferenceId) === '') {
            throw InvalidConfigurationException::forEmptyField('external_reference_id');
        }

        try {
            $response = $this->httpClient->request('GET', sprintf(self::RESOURCE_PATH, rawurlencode($externalReferenceId)), [
                'headers' => [
                    'Authorization' => $this->authClient->getAccessToken()->authorizationHeader(),
                    'partner-software-name' => $this->partnerSoftware->name,
                    'partner-software-version' => $this->partnerSoftware->version,
                ],
            ]);
        } catch (ConnectException $exception) {
            throw NetworkException::fromConnectException($exception);
        } catch (RequestException $exception) {
            throw ApiException::fromRequestException($exception);
        }

        return $this->parseBolepixResponse($response);
    }

    /**
     * Atualiza (PATCH) um bolepix já emitido. Apenas os campos informados em
     * `$request` são enviados à API.
     */
    public function update(string $externalReferenceId, UpdateBolepixRequest $request): Bolepix
    {
        if (trim($externalReferenceId) === '') {
            throw InvalidConfigurationException::forEmptyField('external_reference_id');
        }

        try {
            $response = $this->httpClient->request('PATCH', sprintf(self::RESOURCE_PATH, rawurlencode($externalReferenceId)), [
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

        return $this->parseBolepixResponse($response);
    }

    /**
     * Baixa o PDF do boleto já emitido, a partir do `external_reference_id`
     * informado na emissão. Retorna o conteúdo binário do arquivo.
     */
    public function getPdf(string $externalReferenceId): string
    {
        if (trim($externalReferenceId) === '') {
            throw InvalidConfigurationException::forEmptyField('external_reference_id');
        }

        try {
            $response = $this->httpClient->request('GET', sprintf(self::PDF_PATH, rawurlencode($externalReferenceId)), [
                'headers' => [
                    'Authorization' => $this->authClient->getAccessToken()->authorizationHeader(),
                    'partner-software-name' => $this->partnerSoftware->name,
                    'partner-software-version' => $this->partnerSoftware->version,
                ],
            ]);
        } catch (ConnectException $exception) {
            throw NetworkException::fromConnectException($exception);
        } catch (RequestException $exception) {
            throw ApiException::fromRequestException($exception);
        }

        $pdf = (string) $response->getBody();

        if (! str_starts_with($pdf, '%PDF-')) {
            throw MalformedResponseException::forReason('corpo da resposta não é um PDF válido.');
        }

        return $pdf;
    }

    /**
     * Cancela um bolepix já emitido, a partir do `external_reference_id`
     * informado na emissão.
     */
    public function cancel(string $externalReferenceId): void
    {
        if (trim($externalReferenceId) === '') {
            throw InvalidConfigurationException::forEmptyField('external_reference_id');
        }

        try {
            $this->httpClient->request('PUT', sprintf(self::CANCEL_PATH, rawurlencode($externalReferenceId)), [
                'headers' => [
                    'Authorization' => $this->authClient->getAccessToken()->authorizationHeader(),
                    'partner-software-name' => $this->partnerSoftware->name,
                    'partner-software-version' => $this->partnerSoftware->version,
                ],
            ]);
        } catch (ConnectException $exception) {
            throw NetworkException::fromConnectException($exception);
        } catch (RequestException $exception) {
            throw ApiException::fromRequestException($exception);
        }
    }

    private function defaultBillingScheme(): ?string
    {
        return match ($this->environment) {
            Environment::Sandbox => self::SANDBOX_BILLING_SCHEME,
            Environment::Production => self::PRODUCTION_BILLING_SCHEME,
            null => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyDefaultBillingScheme(array $payload, string $billingScheme): array
    {
        $paymentMethod = $payload['payment_method'] ?? null;
        $paymentMethod = is_array($paymentMethod) ? $paymentMethod : [];

        $bankSlip = $paymentMethod['bank_slip'] ?? null;
        $bankSlip = is_array($bankSlip) ? $bankSlip : [];

        $paymentMethod['bank_slip'] = $bankSlip + ['billing_scheme' => $billingScheme];
        $payload['payment_method'] = $paymentMethod;

        return $payload;
    }

    private function parseBolepixResponse(ResponseInterface $response): Bolepix
    {
        return BolepixResponseParser::parseJson((string) $response->getBody());
    }
}
