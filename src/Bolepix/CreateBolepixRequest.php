<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Bolepix;

use DateTimeInterface;
use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

/**
 * Dados necessários para emitir um bolepix (boleto híbrido com Pix).
 */
final readonly class CreateBolepixRequest
{
    /**
     * Formato exigido pela API: exatamente 26 caracteres alfanuméricos
     * maiúsculos (ex.: um ULID), confirmado via erro de validação real
     * do endpoint (`ECMA 262 regex "^[A-Z0-9]{26}$"`).
     */
    private const string EXTERNAL_REFERENCE_ID_PATTERN = '/^[A-Z0-9]{26}$/';

    public function __construct(
        public string $externalReferenceId,
        public float $amount,
        public DateTimeInterface|string $dueDate,
        public Payer $payer,
        public ?string $description = null,
        public ?int $daysAfterDueDate = null,
        public ?Fees $fees = null,
        public ?PaymentMethod $paymentMethod = null,
        public ?string $origin = null,
    ) {
        if (trim($this->externalReferenceId) === '') {
            throw InvalidConfigurationException::forEmptyField('external_reference_id');
        }

        if (preg_match(self::EXTERNAL_REFERENCE_ID_PATTERN, $this->externalReferenceId) !== 1) {
            throw InvalidConfigurationException::forInvalidValue(
                'external_reference_id',
                'deve conter exatamente 26 caracteres alfanuméricos maiúsculos (A-Z, 0-9), ex.: um ULID.',
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'external_reference_id' => $this->externalReferenceId,
            'amount' => $this->amount,
            'due_date' => $this->dueDate instanceof DateTimeInterface ? $this->dueDate->format('Y-m-d') : $this->dueDate,
            'payer' => $this->payer->toArray(),
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->daysAfterDueDate !== null) {
            $data['days_after_due_date'] = $this->daysAfterDueDate;
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
