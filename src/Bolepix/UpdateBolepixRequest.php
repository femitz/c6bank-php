<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

use DateTimeInterface;

/**
 * Dados para atualizar (PATCH) um bolepix já emitido. Todos os campos são
 * opcionais — apenas os informados são enviados à API.
 */
final readonly class UpdateBolepixRequest
{
    public function __construct(
        public ?float $amount = null,
        public DateTimeInterface|string|null $dueDate = null,
        public ?string $description = null,
        public ?int $daysAfterDueDate = null,
        public ?UpdatePayerOptions $payer = null,
        public ?Fees $fees = null,
        public ?PaymentMethod $paymentMethod = null,
        public ?string $origin = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->amount !== null) {
            $data['amount'] = $this->amount;
        }

        if ($this->dueDate !== null) {
            $data['due_date'] = $this->dueDate instanceof DateTimeInterface ? $this->dueDate->format('Y-m-d') : $this->dueDate;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->daysAfterDueDate !== null) {
            $data['days_after_due_date'] = $this->daysAfterDueDate;
        }

        if ($this->payer instanceof UpdatePayerOptions) {
            $payer = $this->payer->toArray();

            if ($payer !== []) {
                $data['payer'] = $payer;
            }
        }

        if ($this->fees instanceof Fees) {
            $fees = $this->fees->toArray();

            if ($fees !== []) {
                $data['fees'] = $fees;
            }
        }

        if ($this->paymentMethod instanceof PaymentMethod) {
            $paymentMethod = $this->paymentMethod->toArray();

            if ($paymentMethod !== []) {
                $data['payment_method'] = $paymentMethod;
            }
        }

        if ($this->origin !== null) {
            $data['origin'] = $this->origin;
        }

        return $data;
    }
}
