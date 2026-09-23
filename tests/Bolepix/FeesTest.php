<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Bolepix\Fees;

it('converts every field to the array shape expected by the api', function (): void {
    $fees = new Fees(
        fineValue: 10,
        fineDeadline: 1,
        fineType: 'FIXED_VALUE',
        interestValue: 0.33,
        interestDeadline: 1,
        interestType: 'VALUE_PER_DAY',
        discountType: 'VALUE_PER_DAY',
        firstDiscountValue: 5,
        firstDiscountDeadline: 10,
    );

    expect($fees->toArray())->toBe([
        'fine_value' => 10.0,
        'fine_deadline' => 1,
        'fine_type' => 'FIXED_VALUE',
        'interest_value' => 0.33,
        'interest_deadline' => 1,
        'interest_type' => 'VALUE_PER_DAY',
        'discount_type' => 'VALUE_PER_DAY',
        'first_discount_value' => 5.0,
        'first_discount_deadline' => 10,
    ]);
});

it('omits every field when none are set', function (): void {
    expect(new Fees)->toArray()->toBe([]);
});
