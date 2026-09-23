<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

/**
 * Configuração dos meios de pagamento (boleto e/ou Pix) de um bolepix.
 */
final readonly class PaymentMethod
{
    public function __construct(
        public ?BankSlipOptions $bankSlip = null,
        public ?PixOptions $pix = null,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->bankSlip instanceof BankSlipOptions) {
            $bankSlip = $this->bankSlip->toArray();

            if ($bankSlip !== []) {
                $data['bank_slip'] = $bankSlip;
            }
        }

        if ($this->pix instanceof PixOptions) {
            $data['pix'] = $this->pix->toArray();
        }

        return $data;
    }
}
