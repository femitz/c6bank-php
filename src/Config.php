<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp;

use Femitz\C6BankPhp\Auth\Certificate;
use Femitz\C6BankPhp\Auth\Credentials;
use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

final readonly class Config
{
    public string $baseUrl;

    public function __construct(
        public Credentials $credentials,
        public Certificate $certificate,
        Environment|string $environment = Environment::Sandbox,
        public int $tokenSafetyMarginSeconds = 30,
    ) {
        $baseUrl = rtrim($environment instanceof Environment ? $environment->baseUrl() : $environment, '/');

        if ($baseUrl === '') {
            throw InvalidConfigurationException::forEmptyField('base_url');
        }

        $this->baseUrl = $baseUrl;
    }
}
