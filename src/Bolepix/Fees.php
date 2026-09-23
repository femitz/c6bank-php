<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

/**
 * Multa, juros e desconto opcionais de um bolepix.
 */
final readonly class Fees
{
    public function __construct(
        public ?float $fineValue = null,
        public ?int $fineDeadline = null,
        public ?string $fineType = null,
        public ?float $interestValue = null,
        public ?int $interestDeadline = null,
        public ?string $interestType = null,
        public ?string $discountType = null,
        public ?float $firstDiscountValue = null,
        public ?int $firstDiscountDeadline = null,
    ) {}

    /**
     * @return array<string, int|float|string>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->fineValue !== null) {
            $data['fine_value'] = $this->fineValue;
        }

        if ($this->fineDeadline !== null) {
            $data['fine_deadline'] = $this->fineDeadline;
        }

        if ($this->fineType !== null) {
            $data['fine_type'] = $this->fineType;
        }

        if ($this->interestValue !== null) {
            $data['interest_value'] = $this->interestValue;
        }

        if ($this->interestDeadline !== null) {
            $data['interest_deadline'] = $this->interestDeadline;
        }

        if ($this->interestType !== null) {
            $data['interest_type'] = $this->interestType;
        }

        if ($this->discountType !== null) {
            $data['discount_type'] = $this->discountType;
        }

        if ($this->firstDiscountValue !== null) {
            $data['first_discount_value'] = $this->firstDiscountValue;
        }

        if ($this->firstDiscountDeadline !== null) {
            $data['first_discount_deadline'] = $this->firstDiscountDeadline;
        }

        return $data;
    }
}
