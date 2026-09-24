<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Webhook;

/**
 * Webhook registrado, retornado pela API do C6 Bank.
 */
final readonly class Webhook
{
    public function __construct(
        public WebhookService $service,
        public string $clientId,
        public string $url,
        public string $createdAt,
    ) {}
}
