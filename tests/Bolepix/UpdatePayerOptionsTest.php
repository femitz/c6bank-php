<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Bolepix\Address;
use Femitz\C6BankPhp\Bolepix\UpdatePayerOptions;

function makeUpdatePayerAddress(): Address
{
    return new Address('Av. Nove de Julho, 3186', 'Jardim Paulista', 'São Paulo', 'SP', '01406000');
}

it('converts every field to the array shape expected by the api', function (): void {
    $options = new UpdatePayerOptions(
        email: 'pagador@email.com.br',
        address: makeUpdatePayerAddress(),
    );

    expect($options->toArray())->toBe([
        'email' => 'pagador@email.com.br',
        'address' => makeUpdatePayerAddress()->toArray(),
    ]);
});

it('omits every field when none are set', function (): void {
    expect(new UpdatePayerOptions)->toArray()->toBe([]);
});
