<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

/**
 * Opções específicas do boleto de um bolepix.
 */
final readonly class BankSlipOptions
{
    /**
     * @param  list<string>  $instructions
     */
    public function __construct(
        public ?string $ourNumber = null,
        public ?string $billingScheme = null,
        public ?string $yourNumber = null,
        public array $instructions = [],
    ) {}

    /**
     * @return array{our_number?: string, billing_scheme?: string, your_number?: string, instructions?: list<string>}
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->ourNumber !== null) {
            $data['our_number'] = $this->ourNumber;
        }

        if ($this->billingScheme !== null) {
            $data['billing_scheme'] = $this->billingScheme;
        }

        if ($this->yourNumber !== null) {
            $data['your_number'] = $this->yourNumber;
        }

        if ($this->instructions !== []) {
            $data['instructions'] = $this->instructions;
        }

        return $data;
    }
}
