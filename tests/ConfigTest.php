<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Environment;
use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

it('resolves the base url from an Environment case', function (): void {
    $config = makeConfig(Environment::Sandbox);

    expect($config->baseUrl)->toBe('https://baas-api-sandbox.c6bank.info');
});

it('resolves the base url from a custom string', function (): void {
    $config = makeConfig('https://example.test');

    expect($config->baseUrl)->toBe('https://example.test');
});

it('trims a trailing slash from a custom base url', function (): void {
    $config = makeConfig('https://example.test/');

    expect($config->baseUrl)->toBe('https://example.test');
});

it('rejects an empty base url', function (): void {
    makeConfig('');
})->throws(InvalidConfigurationException::class);

it('defaults the token safety margin to 30 seconds', function (): void {
    $config = makeConfig();

    expect($config->tokenSafetyMarginSeconds)->toBe(30);
});

it('allows overriding the token safety margin', function (): void {
    $config = makeConfig(tokenSafetyMarginSeconds: 60);

    expect($config->tokenSafetyMarginSeconds)->toBe(60);
});
