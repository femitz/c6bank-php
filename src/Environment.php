<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp;

/**
 * URLs base conhecidas da API do C6 Bank.
 */
enum Environment: string
{
    case Sandbox = 'https://baas-api-sandbox.c6bank.info';
    case Production = 'https://baas-api.c6bank.info';

    public function baseUrl(): string
    {
        return $this->value;
    }
}
