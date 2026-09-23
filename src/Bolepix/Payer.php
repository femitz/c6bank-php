<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

/**
 * Pagador de um bolepix.
 */
final readonly class Payer
{
    public function __construct(
        public string $name,
        public string $taxId,
        public Address $address,
        public ?string $email = null,
    ) {
        if (trim($this->name) === '') {
            throw InvalidConfigurationException::forEmptyField('payer.name');
        }

        if (trim($this->taxId) === '') {
            throw InvalidConfigurationException::forEmptyField('payer.tax_id');
        }
    }

    /**
     * @return array{name: string, tax_id: string, address: array{address: string, neighborhood: string, city: string, state: string, zip_code: string}, email?: string}
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'tax_id' => $this->taxId,
            'address' => $this->address->toArray(),
        ];

        if ($this->email !== null) {
            $data['email'] = $this->email;
        }

        return $data;
    }
}
