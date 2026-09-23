<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Bolepix\BankSlipOptions;

it('converts every field to the array shape expected by the api', function (): void {
    $options = new BankSlipOptions(
        ourNumber: '0000003048',
        billingScheme: '15',
        yourNumber: '0000003048',
        instructions: ['Não receber após o vencimento', 'Multa de 2% após vencimento'],
    );

    expect($options->toArray())->toBe([
        'our_number' => '0000003048',
        'billing_scheme' => '15',
        'your_number' => '0000003048',
        'instructions' => ['Não receber após o vencimento', 'Multa de 2% após vencimento'],
    ]);
});

it('omits every field when none are set', function (): void {
    expect(new BankSlipOptions)->toArray()->toBe([]);
});
