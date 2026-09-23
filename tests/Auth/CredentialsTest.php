<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Auth\Credentials;
use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

it('holds client id and secret', function (): void {
    $credentials = new Credentials('client-id', 'client-secret');

    expect($credentials->clientId)->toBe('client-id')
        ->and($credentials->clientSecret)->toBe('client-secret');
});

it('rejects an empty client id', function (): void {
    new Credentials('', 'client-secret');
})->throws(InvalidConfigurationException::class, 'O campo "client_id" não pode ser vazio.');

it('rejects a blank client id', function (): void {
    new Credentials('   ', 'client-secret');
})->throws(InvalidConfigurationException::class);

it('rejects an empty client secret', function (): void {
    new Credentials('client-id', '');
})->throws(InvalidConfigurationException::class, 'O campo "client_secret" não pode ser vazio.');
