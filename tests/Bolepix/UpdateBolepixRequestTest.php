<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Bolepix\Address;
use Femitz\C6BankPhp\Bolepix\BankSlipOptions;
use Femitz\C6BankPhp\Bolepix\Fees;
use Femitz\C6BankPhp\Bolepix\PaymentMethod;
use Femitz\C6BankPhp\Bolepix\UpdateBolepixRequest;
use Femitz\C6BankPhp\Bolepix\UpdatePayerOptions;

it('converts every field to the array shape expected by the api', function (): void {
    $request = new UpdateBolepixRequest(
        amount: 150,
        dueDate: '2026-12-30',
        description: 'Mensalidade referente a Junho/2026',
        daysAfterDueDate: 30,
        payer: new UpdatePayerOptions(
            email: 'pagador@email.com.br',
            address: new Address('Av. Nove de Julho, 3186', 'Jardim Paulista', 'São Paulo', 'SP', '01406000'),
        ),
        fees: new Fees(fineValue: 10, fineType: 'FIXED_VALUE'),
        paymentMethod: new PaymentMethod(
            bankSlip: new BankSlipOptions(yourNumber: '0000003048', instructions: ['Não receber após o vencimento']),
        ),
        origin: 'e-commerce',
    );

    expect($request->toArray())->toBe([
        'amount' => 150.0,
        'due_date' => '2026-12-30',
        'description' => 'Mensalidade referente a Junho/2026',
        'days_after_due_date' => 30,
        'payer' => [
            'email' => 'pagador@email.com.br',
            'address' => new Address('Av. Nove de Julho, 3186', 'Jardim Paulista', 'São Paulo', 'SP', '01406000')->toArray(),
        ],
        'fees' => ['fine_value' => 10.0, 'fine_type' => 'FIXED_VALUE'],
        'payment_method' => [
            'bank_slip' => ['your_number' => '0000003048', 'instructions' => ['Não receber após o vencimento']],
        ],
        'origin' => 'e-commerce',
    ]);
});

it('formats a DateTimeInterface due date as Y-m-d', function (): void {
    $request = new UpdateBolepixRequest(dueDate: new DateTimeImmutable('2026-12-30 10:00:00'));

    expect($request->toArray()['due_date'])->toBe('2026-12-30');
});

it('returns an empty array when nothing is set', function (): void {
    expect((new UpdateBolepixRequest)->toArray())->toBeEmpty();
});

it('omits fees, payer and payment_method when they have no fields set', function (): void {
    $request = new UpdateBolepixRequest(
        payer: new UpdatePayerOptions,
        fees: new Fees,
        paymentMethod: new PaymentMethod,
    );

    expect($request->toArray())
        ->not->toHaveKey('payer')
        ->not->toHaveKey('fees')
        ->not->toHaveKey('payment_method');
});
