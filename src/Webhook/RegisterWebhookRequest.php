<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Webhook;

use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

/**
 * Dados para registrar um webhook de notificações para um serviço da API
 * do C6 Bank (por padrão, `BANK_SLIP` — notificações de bolepix).
 */
final readonly class RegisterWebhookRequest
{
    public function __construct(
        public string $url,
        public WebhookService $service = WebhookService::BankSlip,
    ) {
        if (trim($this->url) === '') {
            throw InvalidConfigurationException::forEmptyField('url');
        }

        if (filter_var($this->url, FILTER_VALIDATE_URL) === false) {
            throw InvalidConfigurationException::forInvalidValue('url', 'deve ser uma URL válida.');
        }
    }

    /**
     * @return array{url: string, service: string}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'service' => $this->service->value,
        ];
    }
}
