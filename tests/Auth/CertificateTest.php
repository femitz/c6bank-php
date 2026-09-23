<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Auth\Certificate;
use Femitz\C6BankPhp\Exceptions\CertificateFileNotFoundException;

it('builds guzzle-compatible options without a password', function (): void {
    $files = makeCertificateFiles();

    $certificate = new Certificate($files['cert'], $files['key']);

    expect($certificate->toCertOption())->toBe($files['cert'])
        ->and($certificate->toSslKeyOption())->toBe($files['key']);
});

it('builds guzzle-compatible options with a password', function (): void {
    $files = makeCertificateFiles();

    $certificate = new Certificate($files['cert'], $files['key'], 'cert-pass', 'key-pass');

    expect($certificate->toCertOption())->toBe([$files['cert'], 'cert-pass'])
        ->and($certificate->toSslKeyOption())->toBe([$files['key'], 'key-pass']);
});

it('rejects a missing certificate file', function (): void {
    $files = makeCertificateFiles();

    new Certificate('/path/does/not/exist.crt', $files['key']);
})->throws(CertificateFileNotFoundException::class);

it('rejects a missing key file', function (): void {
    $files = makeCertificateFiles();

    new Certificate($files['cert'], '/path/does/not/exist.key');
})->throws(CertificateFileNotFoundException::class);
