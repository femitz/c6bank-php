<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

/**
 * Chave Pix associada a um bolepix.
 */
final readonly class PixOptions
{
    public function __construct(
        public string $key,
        public string $type,
    ) {
        if (trim($this->key) === '') {
            throw InvalidConfigurationException::forEmptyField('payment_method.pix.key');
        }

        if (trim($this->type) === '') {
            throw InvalidConfigurationException::forEmptyField('payment_method.pix.type');
        }
    }

    /**
     * @return array{key: string, type: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
        ];
    }
}
