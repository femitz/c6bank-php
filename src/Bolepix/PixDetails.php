<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

/**
 * Dados do Pix emitido, retornados pela API do C6 Bank.
 */
final readonly class PixDetails
{
    public function __construct(
        public string $qrCode,
        public string $imageContent,
        public string $mimeType,
        public string $reference,
    ) {}
}
