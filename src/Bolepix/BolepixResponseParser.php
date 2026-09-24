<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

use Femitz\C6BankPhp\Exceptions\MalformedResponseException;
use JsonException;

/**
 * Parser compartilhado do payload de um bolepix, usado tanto pelas
 * respostas da API (`BolepixClient`) quanto pelo corpo de notificações de
 * webhook (que, no evento `CREATED`, tem exatamente o mesmo formato).
 */
final class BolepixResponseParser
{
    public static function parseJson(string $json): Bolepix
    {
        try {
            $decoded = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw MalformedResponseException::forReason('corpo da resposta não é um JSON válido.');
        }

        if (! is_array($decoded)) {
            throw MalformedResponseException::forReason('corpo da resposta não é um objeto JSON.');
        }

        return self::parseArray($decoded);
    }

    /**
     * @param  array<array-key, mixed>  $decoded
     */
    public static function parseArray(array $decoded): Bolepix
    {
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
                $bankSlip = self::parseBankSlipDetails($bankSlipData);
            }

            $pixData = $paymentMethod['pix'] ?? null;

            if (is_array($pixData)) {
                $pix = self::parsePixDetails($pixData);
            }
        }

        $payerData = $decoded['payer'] ?? null;
        $payer = is_array($payerData) ? self::parsePayerDetails($payerData) : null;

        $feesData = $decoded['fees'] ?? null;
        $fees = is_array($feesData) ? self::parseFeesDetails($feesData) : null;

        return new Bolepix(
            id: $id,
            externalReferenceId: $externalReferenceId,
            amount: (float) $amount,
            dueDate: $dueDate,
            bankSlip: $bankSlip,
            pix: $pix,
            emissionDate: self::parseOptionalString($decoded, 'emission_date'),
            description: self::parseOptionalString($decoded, 'description'),
            daysAfterDueDate: self::parseOptionalInt($decoded, 'days_after_due_date'),
            status: self::parseOptionalString($decoded, 'status'),
            payer: $payer,
            fees: $fees,
            origin: self::parseOptionalString($decoded, 'origin'),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function parseBankSlipDetails(array $data): BankSlipDetails
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
    private static function parsePixDetails(array $data): PixDetails
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
    private static function parsePayerDetails(array $data): Payer
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
            address: self::parseAddressDetails($addressData),
            email: self::parseOptionalString($data, 'email'),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function parseAddressDetails(array $data): Address
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
    private static function parseFeesDetails(array $data): Fees
    {
        return new Fees(
            fineValue: self::parseOptionalFloat($data, 'fine_value'),
            fineDeadline: self::parseOptionalInt($data, 'fine_deadline'),
            fineType: self::parseOptionalString($data, 'fine_type'),
            interestValue: self::parseOptionalFloat($data, 'interest_value'),
            interestDeadline: self::parseOptionalInt($data, 'interest_deadline'),
            interestType: self::parseOptionalString($data, 'interest_type'),
            discountType: self::parseOptionalString($data, 'discount_type'),
            firstDiscountValue: self::parseOptionalFloat($data, 'first_discount_value'),
            firstDiscountDeadline: self::parseOptionalInt($data, 'first_discount_deadline'),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function parseOptionalString(array $data, string $field): ?string
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
    private static function parseOptionalInt(array $data, string $field): ?int
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
    private static function parseOptionalFloat(array $data, string $field): ?float
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
