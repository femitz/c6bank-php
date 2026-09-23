<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Exceptions;

final class CertificateFileNotFoundException extends InvalidConfigurationException
{
    public static function forPath(string $path, string $label): self
    {
        return new self(sprintf('Arquivo de %s não encontrado ou ilegível: "%s".', $label, $path));
    }
}
