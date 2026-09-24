<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Femitz\C6BankPhp\Exceptions\MalformedResponseException;
use Femitz\C6BankPhp\Webhook\BolepixNotification;
use Femitz\C6BankPhp\Webhook\BolepixNotificationStatus;

/**
 * Endpoint que recebe as notificações de bolepix no endereço registrado em
 * `WebhookClient::register()`. Rode com, por exemplo:
 *   php -S localhost:8000 docs/examples/webhook-notification.php
 */
$rawBody = file_get_contents('php://input');

if ($rawBody === false || $rawBody === '') {
    http_response_code(400);
    exit;
}

try {
    $notification = BolepixNotification::fromJson($rawBody);
} catch (MalformedResponseException $exception) {
    http_response_code(400);
    fwrite(STDERR, "Notificação inválida: {$exception->getMessage()}\n");
    exit;
}

match ($notification->status) {
    BolepixNotificationStatus::Created => error_log(sprintf(
        'Bolepix %s criado (external_reference_id=%s).',
        $notification->bolepix?->id,
        $notification->bolepix?->externalReferenceId,
    )),
    BolepixNotificationStatus::Paid => error_log(sprintf(
        'Bolepix %s pago via %s (amount=%.2f).',
        $notification->payment?->externalReferenceId,
        $notification->payment?->paymentMethod,
        $notification->payment?->amount,
    )),
    BolepixNotificationStatus::WaitingConfirmation => error_log(sprintf(
        'Bolepix %s aguardando confirmação de pagamento.',
        $notification->payment?->externalReferenceId,
    )),
};

http_response_code(204);
