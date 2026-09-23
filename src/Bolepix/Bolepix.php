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
    ) {}
}
