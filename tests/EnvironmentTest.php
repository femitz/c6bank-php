<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Environment;

it('exposes the sandbox base url', function (): void {
    expect(Environment::Sandbox->baseUrl())->toBe('https://baas-api-sandbox.c6bank.info');
});

it('exposes the production base url', function (): void {
    expect(Environment::Production->baseUrl())->toBe('https://baas-api.c6bank.info');
});
