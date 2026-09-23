<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Exceptions;

final class MalformedResponseException extends C6BankException
{
    public static function forReason(string $reason): self
    {
        return new self(sprintf('Resposta inesperada do C6 Bank: %s', $reason));
    }
}
