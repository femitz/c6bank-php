<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Bolepix\PixOptions;
use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

it('converts to the array shape expected by the api', function (): void {
    $pix = new PixOptions('123e4567-e89b-12d3-a456-426614174000', 'EVP');

    expect($pix->toArray())->toBe([
        'key' => '123e4567-e89b-12d3-a456-426614174000',
        'type' => 'EVP',
    ]);
});

it('rejects an empty key', function (): void {
    new PixOptions('', 'EVP');
})->throws(InvalidConfigurationException::class, 'O campo "payment_method.pix.key" não pode ser vazio.');

it('rejects an empty type', function (): void {
    new PixOptions('123e4567-e89b-12d3-a456-426614174000', '');
})->throws(InvalidConfigurationException::class, 'O campo "payment_method.pix.type" não pode ser vazio.');
