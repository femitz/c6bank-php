<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Bolepix\BankSlipOptions;
use Femitz\C6BankPhp\Bolepix\PaymentMethod;
use Femitz\C6BankPhp\Bolepix\PixOptions;

it('converts both bank slip and pix options to the array shape expected by the api', function (): void {
    $paymentMethod = new PaymentMethod(
        bankSlip: new BankSlipOptions(ourNumber: '0000003048'),
        pix: new PixOptions('123e4567-e89b-12d3-a456-426614174000', 'EVP'),
    );

    expect($paymentMethod->toArray())->toBe([
        'bank_slip' => ['our_number' => '0000003048'],
        'pix' => ['key' => '123e4567-e89b-12d3-a456-426614174000', 'type' => 'EVP'],
    ]);
});

it('omits bank_slip when it has no fields set', function (): void {
    $paymentMethod = new PaymentMethod(bankSlip: new BankSlipOptions);

    expect($paymentMethod->toArray())->toBeEmpty();
});

it('returns an empty array when nothing is set', function (): void {
    expect(new PaymentMethod)->toArray()->toBe([]);
});
