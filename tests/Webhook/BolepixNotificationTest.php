<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Exceptions\MalformedResponseException;
use Femitz\C6BankPhp\Webhook\BolepixNotification;
use Femitz\C6BankPhp\Webhook\BolepixNotificationStatus;
use Femitz\C6BankPhp\Webhook\WebhookService;

/**
 * @return array<string, mixed>
 */
function notificationCreatedInformation(): array
{
    return [
        'id' => '01KP9CS9RW5EDMMG30ZVE7AASC',
        'external_reference_id' => 'KZUJ0GAT7YI4CUOP3NY651STLW',
        'amount' => 109.25,
        'due_date' => '2026-04-30',
        'payment_method' => [
            'bank_slip' => [
                'originator_id' => '000006242018',
                'billing_scheme' => '21',
                'billing_type' => '3',
                'digitable_line' => '33690.00009   62420.180010   01989.662133   3   14320000010925',
                'bar_code' => '33693143200000109250000062420180010198966213',
                'our_number' => '10198966',
                'number' => '01KP9CSAE9A8KBKF36BP5M5QKP',
            ],
            'pix' => [
                'qr_code' => '00020101021226990014br.gov.bcb.pix2577qrcode-h.c6pix.com/qrs1/v2/cobv/01hf...',
                'image_content' => 'iVBORw0KGgoAAAANSUhEUgAAAfQ...',
                'mime_type' => 'image/png',
                'reference' => 'QRS1TXJ0YVADX5MH56SOJQJXKEOEBMP3B95',
            ],
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function notificationCreatedBody(): array
{
    return [
        'external_id' => '19BE50723DAA4B0C91789457DC',
        'date_time' => '2026-04-15T20:08:35.152525501Z',
        'client_id' => '6abeb8cd-3dda-4f3a-b038-3b55c9d87d6a',
        'partner_id' => '01J6W5RJTVB5CV1Z8QAB1K08QG',
        'service' => 'BANK_SLIP_PIX',
        'status' => 'CREATED',
        'information' => json_encode(notificationCreatedInformation(), JSON_THROW_ON_ERROR),
    ];
}

/**
 * @return array<string, mixed>
 */
function notificationPaidBody(): array
{
    $inner = [
        'id' => '01KP9C34NVB711SM0GT5C0CWVT',
        'status' => 'SUCCESS',
        'payment_date' => '2026-04-15',
        'payment_method' => 'PIX',
        'external_reference_id' => 'P5IMR8UUDN9O8P2LKBN8EUKXCR',
        'amount' => '109.25',
    ];

    return [
        'external_id' => '19BE50723DAA4B0C91789457DC',
        'date_time' => '2026-04-15T20:08:35.152525501Z',
        'client_id' => '6abeb8cd-3dda-4f3a-b038-3b55c9d87d6a',
        'partner_id' => '01J6W5RJTVB5CV1Z8QAB1K08QG',
        'service' => 'BANK_SLIP_PIX',
        'status' => 'PAID',
        'information' => json_encode([
            'information' => json_encode($inner, JSON_THROW_ON_ERROR),
        ], JSON_THROW_ON_ERROR),
    ];
}

/**
 * @return array<string, mixed>
 */
function notificationWaitingConfirmationInformation(): array
{
    return [
        'id' => '01KXH8BDPWA2NRFHJF4JX037DZ',
        'payment_date' => '2026-07-14',
        'payment_method' => 'BANK_SLIP',
        'external_reference_id' => 'CU9D02SEW1Q1OW7KZXSM01MI7B',
        'amount' => '1.00',
    ];
}

/**
 * @return array<string, mixed>
 */
function notificationWaitingConfirmationBody(): array
{
    return [
        'external_id' => '19BE50723DAA4B0C91789457DC',
        'date_time' => '2026-07-14T22:21:46.496081377Z',
        'client_id' => '6abeb8cd-3dda-4f3a-b038-3b55c9d87d6a',
        'partner_id' => '01J6W5RJTVB5CV1Z8QAB1K08QG',
        'service' => 'BANK_SLIP',
        'status' => 'WAITING_CONFIRMATION',
        'information' => json_encode(notificationWaitingConfirmationInformation(), JSON_THROW_ON_ERROR),
    ];
}

it('parses a CREATED notification and exposes the bolepix', function (): void {
    $notification = BolepixNotification::fromJson(json_encode(notificationCreatedBody(), JSON_THROW_ON_ERROR));

    expect($notification->externalId)->toBe('19BE50723DAA4B0C91789457DC')
        ->and($notification->dateTime)->toBe('2026-04-15T20:08:35.152525501Z')
        ->and($notification->clientId)->toBe('6abeb8cd-3dda-4f3a-b038-3b55c9d87d6a')
        ->and($notification->partnerId)->toBe('01J6W5RJTVB5CV1Z8QAB1K08QG')
        ->and($notification->service)->toBe(WebhookService::BankSlipPix)
        ->and($notification->status)->toBe(BolepixNotificationStatus::Created)
        ->and($notification->payment)->toBeNull()
        ->and($notification->bolepix?->id)->toBe('01KP9CS9RW5EDMMG30ZVE7AASC')
        ->and($notification->bolepix?->externalReferenceId)->toBe('KZUJ0GAT7YI4CUOP3NY651STLW')
        ->and($notification->bolepix?->amount)->toBe(109.25)
        ->and($notification->bolepix?->dueDate)->toBe('2026-04-30')
        ->and($notification->bolepix?->bankSlip?->number)->toBe('01KP9CSAE9A8KBKF36BP5M5QKP')
        ->and($notification->bolepix?->pix?->reference)->toBe('QRS1TXJ0YVADX5MH56SOJQJXKEOEBMP3B95');
});

it('parses a PAID notification and exposes payment information', function (): void {
    $notification = BolepixNotification::fromJson(json_encode(notificationPaidBody(), JSON_THROW_ON_ERROR));

    expect($notification->status)->toBe(BolepixNotificationStatus::Paid)
        ->and($notification->bolepix)->toBeNull()
        ->and($notification->payment?->id)->toBe('01KP9C34NVB711SM0GT5C0CWVT')
        ->and($notification->payment?->status)->toBe('SUCCESS')
        ->and($notification->payment?->paymentDate)->toBe('2026-04-15')
        ->and($notification->payment?->paymentMethod)->toBe('PIX')
        ->and($notification->payment?->externalReferenceId)->toBe('P5IMR8UUDN9O8P2LKBN8EUKXCR')
        ->and($notification->payment?->amount)->toBe(109.25);
});

it('parses a WAITING_CONFIRMATION notification without a payment status', function (): void {
    $notification = BolepixNotification::fromJson(json_encode(notificationWaitingConfirmationBody(), JSON_THROW_ON_ERROR));

    expect($notification->status)->toBe(BolepixNotificationStatus::WaitingConfirmation)
        ->and($notification->service)->toBe(WebhookService::BankSlip)
        ->and($notification->bolepix)->toBeNull()
        ->and($notification->payment?->id)->toBe('01KXH8BDPWA2NRFHJF4JX037DZ')
        ->and($notification->payment?->status)->toBeNull()
        ->and($notification->payment?->paymentMethod)->toBe('BANK_SLIP')
        ->and($notification->payment?->amount)->toBe(1.0);
});

it('throws MalformedResponseException on invalid json', function (): void {
    BolepixNotification::fromJson('not-json');
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException on a non-object json body', function (): void {
    BolepixNotification::fromJson('"5"');
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when a top-level field is missing', function (string $field): void {
    $body = notificationCreatedBody();
    unset($body[$field]);

    BolepixNotification::fromJson(json_encode($body, JSON_THROW_ON_ERROR));
})->throws(MalformedResponseException::class)->with([
    'external_id', 'date_time', 'client_id', 'partner_id', 'service', 'status', 'information',
]);

it('throws MalformedResponseException when the service field has an unknown value', function (): void {
    $body = notificationCreatedBody();
    $body['service'] = 'UNKNOWN';

    BolepixNotification::fromJson(json_encode($body, JSON_THROW_ON_ERROR));
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when the status field has an unknown value', function (): void {
    $body = notificationCreatedBody();
    $body['status'] = 'UNKNOWN';

    BolepixNotification::fromJson(json_encode($body, JSON_THROW_ON_ERROR));
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when information is not valid json', function (): void {
    $body = notificationCreatedBody();
    $body['information'] = 'not-json';

    BolepixNotification::fromJson(json_encode($body, JSON_THROW_ON_ERROR));
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when information is not a json object', function (): void {
    $body = notificationCreatedBody();
    $body['information'] = '"5"';

    BolepixNotification::fromJson(json_encode($body, JSON_THROW_ON_ERROR));
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when a payment information field is missing', function (string $field): void {
    $information = notificationWaitingConfirmationInformation();
    unset($information[$field]);

    $body = notificationWaitingConfirmationBody();
    $body['information'] = json_encode($information, JSON_THROW_ON_ERROR);

    BolepixNotification::fromJson(json_encode($body, JSON_THROW_ON_ERROR));
})->throws(MalformedResponseException::class)->with([
    'id', 'external_reference_id', 'payment_date', 'payment_method', 'amount',
]);

it('throws MalformedResponseException when the payment status field is invalid', function (): void {
    $information = notificationWaitingConfirmationInformation();
    $information['status'] = 123;

    $body = notificationWaitingConfirmationBody();
    $body['information'] = json_encode($information, JSON_THROW_ON_ERROR);

    BolepixNotification::fromJson(json_encode($body, JSON_THROW_ON_ERROR));
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when the nested payment information is not valid json', function (): void {
    $body = notificationPaidBody();
    $body['information'] = json_encode(['information' => 'not-json'], JSON_THROW_ON_ERROR);

    BolepixNotification::fromJson(json_encode($body, JSON_THROW_ON_ERROR));
})->throws(MalformedResponseException::class);
