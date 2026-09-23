<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

/**
 * Dados do pagador atualizáveis via PATCH de um bolepix. Diferente de
 * `Payer` (usado na emissão), aqui `name`/`tax_id` não podem ser alterados
 * — a API só aceita `email` e `address`.
 */
final readonly class UpdatePayerOptions
{
    public function __construct(
        public ?string $email = null,
        public ?Address $address = null,
    ) {}

    /**
     * @return array{email?: string, address?: array{address: string, neighborhood: string, city: string, state: string, zip_code: string}}
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->email !== null) {
            $data['email'] = $this->email;
        }

        if ($this->address instanceof Address) {
            $data['address'] = $this->address->toArray();
        }

        return $data;
    }
}
