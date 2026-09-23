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
use JsonException;
use Psr\Http\Message\ResponseInterface;

final readonly class BolepixClient
{
    private const string CREATE_PATH = '/v2/bank_slips';

    private const string RESOURCE_PATH = '/v2/bank_slips/%s';

    private const string PDF_PATH = '/v2/bank_slips/%s/pdf';

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

        $payerData = $decoded['payer'] ?? null;
        $payer = is_array($payerData) ? $this->parsePayerDetails($payerData) : null;

        $feesData = $decoded['fees'] ?? null;
        $fees = is_array($feesData) ? $this->parseFeesDetails($feesData) : null;

        return new Bolepix(
            id: $id,
            externalReferenceId: $externalReferenceId,
            amount: (float) $amount,
            dueDate: $dueDate,
            bankSlip: $bankSlip,
            pix: $pix,
            emissionDate: $this->parseOptionalString($decoded, 'emission_date'),
            description: $this->parseOptionalString($decoded, 'description'),
            daysAfterDueDate: $this->parseOptionalInt($decoded, 'days_after_due_date'),
            status: $this->parseOptionalString($decoded, 'status'),
            payer: $payer,
            fees: $fees,
            origin: $this->parseOptionalString($decoded, 'origin'),
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

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function parsePayerDetails(array $data): Payer
    {
        $name = $data['name'] ?? null;
        $taxId = $data['tax_id'] ?? null;
        $addressData = $data['address'] ?? null;

        if (! is_string($name) || $name === '' || ! is_string($taxId) || $taxId === '' || ! is_array($addressData)) {
            throw MalformedResponseException::forReason('campos de "payer" ausentes ou inválidos.');
        }

        return new Payer(
            name: $name,
            taxId: $taxId,
            address: $this->parseAddressDetails($addressData),
            email: $this->parseOptionalString($data, 'email'),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function parseAddressDetails(array $data): Address
    {
        $address = $data['address'] ?? null;
        $neighborhood = $data['neighborhood'] ?? null;
        $city = $data['city'] ?? null;
        $state = $data['state'] ?? null;
        $zipCode = $data['zip_code'] ?? null;

        if (! is_string($address) || ! is_string($neighborhood) || ! is_string($city) || ! is_string($state) || ! is_string($zipCode)) {
            throw MalformedResponseException::forReason('campos de "payer.address" ausentes ou inválidos.');
        }

        return new Address(
            address: $address,
            neighborhood: $neighborhood,
            city: $city,
            state: $state,
            zipCode: $zipCode,
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function parseFeesDetails(array $data): Fees
    {
        return new Fees(
            fineValue: $this->parseOptionalFloat($data, 'fine_value'),
            fineDeadline: $this->parseOptionalInt($data, 'fine_deadline'),
            fineType: $this->parseOptionalString($data, 'fine_type'),
            interestValue: $this->parseOptionalFloat($data, 'interest_value'),
            interestDeadline: $this->parseOptionalInt($data, 'interest_deadline'),
            interestType: $this->parseOptionalString($data, 'interest_type'),
            discountType: $this->parseOptionalString($data, 'discount_type'),
            firstDiscountValue: $this->parseOptionalFloat($data, 'first_discount_value'),
            firstDiscountDeadline: $this->parseOptionalInt($data, 'first_discount_deadline'),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function parseOptionalString(array $data, string $field): ?string
    {
        $value = $data[$field] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw MalformedResponseException::forReason(sprintf('campo "%s" inválido.', $field));
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function parseOptionalInt(array $data, string $field): ?int
    {
        $value = $data[$field] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_int($value)) {
            throw MalformedResponseException::forReason(sprintf('campo "%s" inválido.', $field));
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function parseOptionalFloat(array $data, string $field): ?float
    {
        $value = $data[$field] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_int($value) && ! is_float($value)) {
            throw MalformedResponseException::forReason(sprintf('campo "%s" inválido.', $field));
        }

        return (float) $value;
    }
}
