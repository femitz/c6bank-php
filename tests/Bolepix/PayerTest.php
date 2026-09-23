<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Bolepix\Address;
use Femitz\C6BankPhp\Bolepix\Payer;
use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

function makeAddress(): Address
{
    return new Address('Av. Nove de Julho, 3186', 'Jardim Paulista', 'São Paulo', 'SP', '01406000');
}

it('converts to the array shape expected by the api with an email', function (): void {
    $payer = new Payer('José da Silva', '12345678910', makeAddress(), 'pagador@email.com.br');

    expect($payer->toArray())->toBe([
        'name' => 'José da Silva',
        'tax_id' => '12345678910',
        'address' => makeAddress()->toArray(),
        'email' => 'pagador@email.com.br',
    ]);
});

it('omits the email when absent', function (): void {
    $payer = new Payer('José da Silva', '12345678910', makeAddress());

    expect($payer->toArray())->not->toHaveKey('email');
});

it('rejects an empty name', function (): void {
    new Payer('', '12345678910', makeAddress());
})->throws(InvalidConfigurationException::class, 'O campo "payer.name" não pode ser vazio.');

it('rejects an empty tax id', function (): void {
    new Payer('José da Silva', '', makeAddress());
})->throws(InvalidConfigurationException::class, 'O campo "payer.tax_id" não pode ser vazio.');

it('strips non-digit characters from the tax id', function (string $taxId, string $expected): void {
    $payer = new Payer('José da Silva', $taxId, makeAddress());

    expect($payer->taxId)->toBe($expected);
})->with([
    'CPF formatado' => ['123.456.789-10', '12345678910'],
    'CNPJ formatado' => ['12.345.678/0001-95', '12345678000195'],
]);

it('rejects a tax id with only non-digit characters', function (): void {
    new Payer('José da Silva', '---', makeAddress());
})->throws(InvalidConfigurationException::class, 'O campo "payer.tax_id" não pode ser vazio.');
