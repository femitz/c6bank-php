<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

/**
 * Dados do boleto emitido, retornados pela API do C6 Bank.
 */
final readonly class BankSlipDetails
{
    public function __construct(
        public string $originatorId,
        public string $billingScheme,
        public string $billingType,
        public string $digitableLine,
        public string $barCode,
        public string $ourNumber,
        public string $number,
    ) {}
}
