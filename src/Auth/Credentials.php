<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Auth;

use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

final readonly class Credentials
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
    ) {
        if (trim($this->clientId) === '') {
            throw InvalidConfigurationException::forEmptyField('client_id');
        }

        if (trim($this->clientSecret) === '') {
            throw InvalidConfigurationException::forEmptyField('client_secret');
        }
    }
}
