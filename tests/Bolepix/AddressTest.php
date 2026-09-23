<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Bolepix\Address;

it('converts to the array shape expected by the api', function (): void {
    $address = new Address(
        address: 'Av. Nove de Julho, 3186',
        neighborhood: 'Jardim Paulista',
        city: 'São Paulo',
        state: 'SP',
        zipCode: '01406000',
    );

    expect($address->toArray())->toBe([
        'address' => 'Av. Nove de Julho, 3186',
        'neighborhood' => 'Jardim Paulista',
        'city' => 'São Paulo',
        'state' => 'SP',
        'zip_code' => '01406000',
    ]);
});

it('strips non-digit characters from the zip code', function (): void {
    $address = new Address(
        address: 'Av. Nove de Julho, 3186',
        neighborhood: 'Jardim Paulista',
        city: 'São Paulo',
        state: 'SP',
        zipCode: '01406-000',
    );

    expect($address->zipCode)->toBe('01406000');
});
