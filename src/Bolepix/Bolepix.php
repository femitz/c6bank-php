<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

/**
 * Bolepix emitido, retornado pela API do C6 Bank.
 */
final readonly class Bolepix
{
    public function __construct(
        public string $id,
        public string $externalReferenceId,
        public float $amount,
        public string $dueDate,
        public ?BankSlipDetails $bankSlip,
        public ?PixDetails $pix,
        public ?string $emissionDate = null,
        public ?string $description = null,
        public ?int $daysAfterDueDate = null,
        public ?string $status = null,
        public ?Payer $payer = null,
        public ?Fees $fees = null,
        public ?string $origin = null,
    ) {}
}
