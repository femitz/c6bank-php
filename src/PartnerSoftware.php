<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp;

use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

/**
 * Identifica o software parceiro que está integrando com a API do C6 Bank,
 * exigido pelos headers `partner-software-name` e `partner-software-version`.
 */
final readonly class PartnerSoftware
{
    public function __construct(
        public string $name,
        public string $version,
    ) {
        if (trim($this->name) === '') {
            throw InvalidConfigurationException::forEmptyField('partner_software_name');
        }

        if (trim($this->version) === '') {
            throw InvalidConfigurationException::forEmptyField('partner_software_version');
        }
    }
}
