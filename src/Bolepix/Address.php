<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

/**
 * Endereço do pagador de um bolepix.
 */
final readonly class Address
{
    public string $zipCode;

    public function __construct(
        public string $address,
        public string $neighborhood,
        public string $city,
        public string $state,
        string $zipCode,
    ) {
        $this->zipCode = $this->onlyDigits($zipCode);
    }

    /**
     * @return array{address: string, neighborhood: string, city: string, state: string, zip_code: string}
     */
    public function toArray(): array
    {
        return [
            'address' => $this->address,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zipCode,
        ];
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? $value;
    }
}
