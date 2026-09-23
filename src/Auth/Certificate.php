<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Auth;

use Femitz\C6BankPhp\Exceptions\CertificateFileNotFoundException;

/**
 * Certificado cliente (mTLS) exigido pela API do C6 Bank.
 */
final readonly class Certificate
{
    public function __construct(
        public string $certPath,
        public string $keyPath,
        public ?string $certPassword = null,
        public ?string $keyPassword = null,
    ) {
        $this->assertReadable($this->certPath, 'certificado');
        $this->assertReadable($this->keyPath, 'chave privada');
    }

    /**
     * @return string|array{0: string, 1: string}
     */
    public function toCertOption(): string|array
    {
        return $this->certPassword === null ? $this->certPath : [$this->certPath, $this->certPassword];
    }

    /**
     * @return string|array{0: string, 1: string}
     */
    public function toSslKeyOption(): string|array
    {
        return $this->keyPassword === null ? $this->keyPath : [$this->keyPath, $this->keyPassword];
    }

    private function assertReadable(string $path, string $label): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw CertificateFileNotFoundException::forPath($path, $label);
        }
    }
}
