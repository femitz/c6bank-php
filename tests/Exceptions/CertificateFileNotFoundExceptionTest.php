<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Exceptions\CertificateFileNotFoundException;

it('builds a message for a missing certificate file', function (): void {
    $exception = CertificateFileNotFoundException::forPath('/path/to/cert.crt', 'certificado');

    expect($exception->getMessage())->toBe('Arquivo de certificado não encontrado ou ilegível: "/path/to/cert.crt".');
});
