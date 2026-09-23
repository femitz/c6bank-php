<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Exceptions;

use GuzzleHttp\Exception\ConnectException;

final class NetworkException extends C6BankException
{
    public static function fromConnectException(ConnectException $exception): self
    {
        return new self(
            message: sprintf('Falha de conexão com o C6 Bank: %s', $exception->getMessage()),
            previous: $exception,
        );
    }
}
