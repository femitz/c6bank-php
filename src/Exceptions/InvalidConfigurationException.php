<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Exceptions;

class InvalidConfigurationException extends C6BankException
{
    public static function forEmptyField(string $field): self
    {
        return new self(sprintf('O campo "%s" não pode ser vazio.', $field));
    }
}
