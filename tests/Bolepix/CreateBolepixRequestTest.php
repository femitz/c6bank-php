<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Bolepix\Address;
use Femitz\C6BankPhp\Bolepix\BankSlipOptions;
use Femitz\C6BankPhp\Bolepix\CreateBolepixRequest;
use Femitz\C6BankPhp\Bolepix\Fees;
use Femitz\C6BankPhp\Bolepix\Payer;
use Femitz\C6BankPhp\Bolepix\PaymentMethod;
use Femitz\C6BankPhp\Bolepix\PixOptions;
use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

function makePayer(): Payer
{
    return new Payer(
        name: 'José da Silva',
        taxId: '12345678910',
        address: new Address('Av. Nove de Julho, 3186', 'Jardim Paulista', 'São Paulo', 'SP', '01406000'),
        email: 'pagador@email.com.br',
    );
}

it('converts every field to the array shape expected by the api', function (): void {
    $request = new CreateBolepixRequest(
        externalReferenceId: '01KP640RNSYXH9G41GR27RTAWP',
        amount: 150,
        dueDate: '2026-12-30',
        payer: makePayer(),
        description: 'Mensalidade referente a Junho/2026',
        daysAfterDueDate: 10,
        fees: new Fees(fineValue: 10, fineType: 'FIXED_VALUE'),
        paymentMethod: new PaymentMethod(
            bankSlip: new BankSlipOptions(ourNumber: '0000003048'),
            pix: new PixOptions('123e4567-e89b-12d3-a456-426614174000', 'EVP'),
        ),
        origin: 'e-commerce',
    );

    expect($request->toArray())->toBe([
        'external_reference_id' => '01KP640RNSYXH9G41GR27RTAWP',
        'amount' => 150.0,
        'due_date' => '2026-12-30',
        'payer' => makePayer()->toArray(),
        'description' => 'Mensalidade referente a Junho/2026',
        'days_after_due_date' => 10,
        'fees' => ['fine_value' => 10.0, 'fine_type' => 'FIXED_VALUE'],
        'payment_method' => [
            'bank_slip' => ['our_number' => '0000003048'],
            'pix' => ['key' => '123e4567-e89b-12d3-a456-426614174000', 'type' => 'EVP'],
        ],
        'origin' => 'e-commerce',
    ]);
});

it('formats a DateTimeInterface due date as Y-m-d', function (): void {
    $request = new CreateBolepixRequest(
        externalReferenceId: '01KP640RNSYXH9G41GR27RTAWP',
        amount: 150,
        dueDate: new DateTimeImmutable('2026-12-30 10:00:00'),
        payer: makePayer(),
    );

    expect($request->toArray()['due_date'])->toBe('2026-12-30');
});

it('omits every optional field when absent', function (): void {
    $request = new CreateBolepixRequest(
        externalReferenceId: '01KP640RNSYXH9G41GR27RTAWP',
        amount: 150,
        dueDate: '2026-12-30',
        payer: makePayer(),
    );

    expect($request->toArray())->toBe([
        'external_reference_id' => '01KP640RNSYXH9G41GR27RTAWP',
        'amount' => 150.0,
        'due_date' => '2026-12-30',
        'payer' => makePayer()->toArray(),
    ]);
});

it('omits fees and payment_method when they have no fields set', function (): void {
    $request = new CreateBolepixRequest(
        externalReferenceId: '01KP640RNSYXH9G41GR27RTAWP',
        amount: 150,
        dueDate: '2026-12-30',
        payer: makePayer(),
        fees: new Fees,
        paymentMethod: new PaymentMethod,
    );

    expect($request->toArray())->not->toHaveKey('fees')
        ->and($request->toArray())->not->toHaveKey('payment_method');
});

it('rejects an empty external reference id', function (): void {
    new CreateBolepixRequest('', 150, '2026-12-30', makePayer());
})->throws(InvalidConfigurationException::class, 'O campo "external_reference_id" não pode ser vazio.');

it('rejects an external reference id with an invalid format', function (string $externalReferenceId): void {
    new CreateBolepixRequest($externalReferenceId, 150, '2026-12-30', makePayer());
})->throws(InvalidConfigurationException::class)->with([
    'too-short' => ['01KP640RNSYXH9G41GR27RTA'],
    'too-long' => ['01KP640RNSYXH9G41GR27RTAWPX'],
    'lowercase' => ['01kp640rnsyxh9g41gr27rtawp'],
    'with-symbols' => ['test-6ab3eeacb2e6b5.1073'],
]);
