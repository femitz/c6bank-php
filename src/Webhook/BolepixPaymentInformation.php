<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Webhook;

/**
 * Informações de pagamento de um bolepix, presentes no campo `information`
 * de notificações com status diferente de `CREATED` (ex.: `PAID`,
 * `WAITING_CONFIRMATION`). O campo `status` só é enviado em alguns status
 * de notificação (ex.: presente em `PAID`, ausente em
 * `WAITING_CONFIRMATION`).
 */
final readonly class BolepixPaymentInformation
{
    public function __construct(
        public string $id,
        public string $externalReferenceId,
        public float $amount,
        public string $paymentDate,
        public string $paymentMethod,
        public ?string $status = null,
    ) {}
}
