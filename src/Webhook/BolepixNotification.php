<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Webhook;

use Femitz\C6BankPhp\Bolepix\Bolepix;
use Femitz\C6BankPhp\Bolepix\BolepixResponseParser;
use Femitz\C6BankPhp\Exceptions\MalformedResponseException;
use JsonException;

/**
 * Notificação de bolepix recebida via webhook (`POST` no endereço
 * registrado em `WebhookClient::register()`).
 *
 * O campo `information` do payload é, ele próprio, uma string JSON. Seu
 * formato depende do `status`:
 * - `CREATED`: o mesmo formato retornado por `BolepixClient::create()`,
 *   exposto em `$bolepix`.
 * - Demais status (`PAID`, `WAITING_CONFIRMATION`, ...): dados de
 *   pagamento, expostos em `$payment`. Em alguns casos (observado em
 *   `PAID`) esse conteúdo vem com mais um nível de codificação, em um
 *   campo aninhado também chamado `information` — tratado de forma
 *   transparente por `fromJson()`.
 */
final readonly class BolepixNotification
{
    private function __construct(
        public string $externalId,
        public string $dateTime,
        public string $clientId,
        public string $partnerId,
        public WebhookService $service,
        public BolepixNotificationStatus $status,
        public ?Bolepix $bolepix,
        public ?BolepixPaymentInformation $payment,
    ) {}

    /**
     * Parseia o corpo bruto (raw) recebido no webhook.
     */
    public static function fromJson(string $json): self
    {
        $decoded = self::decodeJsonObject($json, 'corpo da notificação');

        $externalId = self::requireString($decoded, 'external_id');
        $dateTime = self::requireString($decoded, 'date_time');
        $clientId = self::requireString($decoded, 'client_id');
        $partnerId = self::requireString($decoded, 'partner_id');

        $serviceValue = self::requireString($decoded, 'service');
        $service = WebhookService::tryFrom($serviceValue);

        if (! $service instanceof WebhookService) {
            throw MalformedResponseException::forReason(sprintf('valor "%s" desconhecido para o campo "service".', $serviceValue));
        }

        $statusValue = self::requireString($decoded, 'status');
        $status = BolepixNotificationStatus::tryFrom($statusValue);

        if (! $status instanceof BolepixNotificationStatus) {
            throw MalformedResponseException::forReason(sprintf('valor "%s" desconhecido para o campo "status".', $statusValue));
        }

        $informationJson = self::requireString($decoded, 'information');
        $information = self::decodeJsonObject($informationJson, 'campo "information"');

        $bolepix = null;
        $payment = null;

        if ($status === BolepixNotificationStatus::Created) {
            $bolepix = BolepixResponseParser::parseArray($information);
        } else {
            $payment = self::parsePaymentInformation($information);
        }

        return new self(
            externalId: $externalId,
            dateTime: $dateTime,
            clientId: $clientId,
            partnerId: $partnerId,
            service: $service,
            status: $status,
            bolepix: $bolepix,
            payment: $payment,
        );
    }

    /**
     * @param  array<array-key, mixed>  $information
     */
    private static function parsePaymentInformation(array $information): BolepixPaymentInformation
    {
        $nested = $information['information'] ?? null;

        if (is_string($nested) && $nested !== '') {
            $information = self::decodeJsonObject($nested, 'campo "information.information"');
        }

        $id = self::requireString($information, 'id', 'information.');
        $externalReferenceId = self::requireString($information, 'external_reference_id', 'information.');
        $paymentDate = self::requireString($information, 'payment_date', 'information.');
        $paymentMethod = self::requireString($information, 'payment_method', 'information.');

        $amount = $information['amount'] ?? null;

        if (! is_int($amount) && ! is_float($amount) && (! is_string($amount) || ! is_numeric($amount))) {
            throw MalformedResponseException::forReason('campo "information.amount" ausente ou inválido.');
        }

        $status = $information['status'] ?? null;

        if ($status !== null && (! is_string($status) || $status === '')) {
            throw MalformedResponseException::forReason('campo "information.status" inválido.');
        }

        return new BolepixPaymentInformation(
            id: $id,
            externalReferenceId: $externalReferenceId,
            amount: (float) $amount,
            paymentDate: $paymentDate,
            paymentMethod: $paymentMethod,
            status: $status,
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function requireString(array $data, string $field, string $fieldPrefix = ''): string
    {
        $value = $data[$field] ?? null;

        if (! is_string($value) || $value === '') {
            throw MalformedResponseException::forReason(sprintf('campo "%s%s" ausente ou inválido.', $fieldPrefix, $field));
        }

        return $value;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function decodeJsonObject(string $json, string $context): array
    {
        try {
            $decoded = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw MalformedResponseException::forReason(sprintf('%s não é um JSON válido.', $context));
        }

        if (! is_array($decoded)) {
            throw MalformedResponseException::forReason(sprintf('%s não é um objeto JSON.', $context));
        }

        return $decoded;
    }
}
