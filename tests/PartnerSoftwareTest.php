<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;
use Femitz\C6BankPhp\PartnerSoftware;

it('holds name and version', function (): void {
    $partnerSoftware = new PartnerSoftware('Meu App', '1.0.0');

    expect($partnerSoftware->name)->toBe('Meu App')
        ->and($partnerSoftware->version)->toBe('1.0.0');
});

it('rejects an empty name', function (): void {
    new PartnerSoftware('', '1.0.0');
})->throws(InvalidConfigurationException::class, 'O campo "partner_software_name" não pode ser vazio.');

it('rejects a blank name', function (): void {
    new PartnerSoftware('   ', '1.0.0');
})->throws(InvalidConfigurationException::class);

it('rejects an empty version', function (): void {
    new PartnerSoftware('Meu App', '');
})->throws(InvalidConfigurationException::class, 'O campo "partner_software_version" não pode ser vazio.');
