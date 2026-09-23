<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

use Femitz\C6BankPhp\Auth\AuthClient;
use Femitz\C6BankPhp\Environment;
use Femitz\C6BankPhp\Exceptions\ApiException;
use Femitz\C6BankPhp\Exceptions\MalformedResponseException;
use Femitz\C6BankPhp\Exceptions\NetworkException;
use Femitz\C6BankPhp\PartnerSoftware;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final readonly class BolepixClient
{
    private const string CREATE_PATH = '/v2/bank_slips';

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
        $body = (string) $response->getBody();

        try {
            $decoded = json_decode($body, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw MalformedResponseException::forReason('corpo da resposta não é um JSON válido.');
        }

        if (! is_array($decoded)) {
            throw MalformedResponseException::forReason('corpo da resposta não é um objeto JSON.');
        }

        $id = $decoded['id'] ?? null;
        $externalReferenceId = $decoded['external_reference_id'] ?? null;
        $amount = $decoded['amount'] ?? null;
        $dueDate = $decoded['due_date'] ?? null;

        if (! is_string($id) || $id === '') {
            throw MalformedResponseException::forReason('campo "id" ausente ou inválido.');
        }

        if (! is_string($externalReferenceId) || $externalReferenceId === '') {
            throw MalformedResponseException::forReason('campo "external_reference_id" ausente ou inválido.');
        }

        if (! is_int($amount) && ! is_float($amount)) {
            throw MalformedResponseException::forReason('campo "amount" ausente ou inválido.');
        }

        if (! is_string($dueDate) || $dueDate === '') {
            throw MalformedResponseException::forReason('campo "due_date" ausente ou inválido.');
        }

        $paymentMethod = $decoded['payment_method'] ?? null;
        $bankSlip = null;
        $pix = null;

        if (is_array($paymentMethod)) {
            $bankSlipData = $paymentMethod['bank_slip'] ?? null;

            if (is_array($bankSlipData)) {
                $bankSlip = $this->parseBankSlipDetails($bankSlipData);
            }

            $pixData = $paymentMethod['pix'] ?? null;

            if (is_array($pixData)) {
                $pix = $this->parsePixDetails($pixData);
            }
        }

        return new Bolepix(
            id: $id,
            externalReferenceId: $externalReferenceId,
            amount: (float) $amount,
            dueDate: $dueDate,
            bankSlip: $bankSlip,
            pix: $pix,
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function parseBankSlipDetails(array $data): BankSlipDetails
    {
        $originatorId = $data['originator_id'] ?? null;
        $billingScheme = $data['billing_scheme'] ?? null;
        $billingType = $data['billing_type'] ?? null;
        $digitableLine = $data['digitable_line'] ?? null;
        $barCode = $data['bar_code'] ?? null;
        $ourNumber = $data['our_number'] ?? null;
        $number = $data['number'] ?? null;

        if (! is_string($originatorId) || ! is_string($billingScheme) || ! is_string($billingType)
            || ! is_string($digitableLine) || ! is_string($barCode) || ! is_string($ourNumber) || ! is_string($number)
        ) {
            throw MalformedResponseException::forReason('campos de "payment_method.bank_slip" ausentes ou inválidos.');
        }

        return new BankSlipDetails(
            originatorId: $originatorId,
            billingScheme: $billingScheme,
            billingType: $billingType,
            digitableLine: $digitableLine,
            barCode: $barCode,
            ourNumber: $ourNumber,
            number: $number,
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function parsePixDetails(array $data): PixDetails
    {
        $qrCode = $data['qr_code'] ?? null;
        $imageContent = $data['image_content'] ?? null;
        $mimeType = $data['mime_type'] ?? null;
        $reference = $data['reference'] ?? null;

        if (! is_string($qrCode) || ! is_string($imageContent) || ! is_string($mimeType) || ! is_string($reference)) {
            throw MalformedResponseException::forReason('campos de "payment_method.pix" ausentes ou inválidos.');
        }

        return new PixDetails(
            qrCode: $qrCode,
            imageContent: $imageContent,
            mimeType: $mimeType,
            reference: $reference,
        );
    }
}
