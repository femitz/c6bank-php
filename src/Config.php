<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp;

use Femitz\C6BankPhp\Auth\Certificate;
use Femitz\C6BankPhp\Auth\Credentials;
use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

final readonly class Config
{
    public string $baseUrl;

    /**
     * Nulo quando `$environment` é uma URL customizada (não um case do enum).
     */
    public ?Environment $environment;

    public function __construct(
        public Credentials $credentials,
        public Certificate $certificate,
        public PartnerSoftware $partnerSoftware,
        Environment|string $environment = Environment::Sandbox,
        public int $tokenSafetyMarginSeconds = 30,
    ) {
        $baseUrl = rtrim($environment instanceof Environment ? $environment->baseUrl() : $environment, '/');

        if ($baseUrl === '') {
            throw InvalidConfigurationException::forEmptyField('base_url');
        }

        $this->baseUrl = $baseUrl;
        $this->environment = $environment instanceof Environment ? $environment : null;
    }
}
