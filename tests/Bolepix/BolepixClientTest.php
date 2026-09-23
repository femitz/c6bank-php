<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Auth\AuthClient;
use Femitz\C6BankPhp\Auth\Credentials;
use Femitz\C6BankPhp\Bolepix\Address;
use Femitz\C6BankPhp\Bolepix\BankSlipOptions;
use Femitz\C6BankPhp\Bolepix\BolepixClient;
use Femitz\C6BankPhp\Bolepix\CreateBolepixRequest;
use Femitz\C6BankPhp\Bolepix\Payer;
use Femitz\C6BankPhp\Bolepix\PaymentMethod;
use Femitz\C6BankPhp\Environment;
use Femitz\C6BankPhp\Exceptions\ApiException;
use Femitz\C6BankPhp\Exceptions\MalformedResponseException;
use Femitz\C6BankPhp\Exceptions\NetworkException;
use Femitz\C6BankPhp\PartnerSoftware;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

function makeCreateBolepixRequest(?PaymentMethod $paymentMethod = null): CreateBolepixRequest
{
    return new CreateBolepixRequest(
        externalReferenceId: '01KP640RNSYXH9G41GR27RTAWP',
        amount: 150,
        dueDate: '2026-12-30',
        payer: new Payer(
            name: 'José da Silva',
            taxId: '12345678910',
            address: new Address('Av. Nove de Julho, 3186', 'Jardim Paulista', 'São Paulo', 'SP', '01406000'),
        ),
        paymentMethod: $paymentMethod,
    );
}

/**
 * @return array<array-key, mixed>
 */
function decodedRequestBody(RequestInterface $request): array
{
    $body = json_decode((string) $request->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($body)) {
        throw new RuntimeException('Expected the request body to decode to an array.');
    }

    return $body;
}

function billingSchemeFromRequest(RequestInterface $request): ?string
{
    $paymentMethod = decodedRequestBody($request)['payment_method'] ?? null;

    if (! is_array($paymentMethod)) {
        return null;
    }

    $bankSlip = $paymentMethod['bank_slip'] ?? null;

    if (! is_array($bankSlip)) {
        return null;
    }

    $billingScheme = $bankSlip['billing_scheme'] ?? null;

    return is_string($billingScheme) ? $billingScheme : null;
}

/**
 * @return array<string, mixed>
 */
function bolepixResponseBody(): array
{
    return [
        'id' => '01HVSBSTN8CCTCTEQT6MC7TD4B',
        'external_reference_id' => '01KP640RNSYXH9G41GR27RTAWP',
        'amount' => 150,
        'due_date' => '2026-12-30',
        'payment_method' => [
            'bank_slip' => [
                'originator_id' => '000000304872',
                'billing_scheme' => '15',
                'billing_type' => '3',
                'digitable_line' => '33690.00009   03048.720001   92241.282133   5   96900000012345',
                'bar_code' => '33695969000000123450000003048720009224128213',
                'our_number' => '0000003048',
                'number' => '12345678',
            ],
            'pix' => [
                'qr_code' => '00020126580014BR.GOV.BCB.PIX...6304B14F',
                'image_content' => 'string',
                'mime_type' => 'image/png',
                'reference' => '123e4567-e89b-12d3-a456-426614174000',
            ],
        ],
    ];
}

it('creates a bolepix and parses the full response', function (): void {
    $mocked = makeBolepixClient([
        new Response(200, [], json_encode(bolepixResponseBody(), JSON_THROW_ON_ERROR)),
    ]);

    $bolepix = $mocked['bolepix']->create(makeCreateBolepixRequest());

    expect($bolepix->id)->toBe('01HVSBSTN8CCTCTEQT6MC7TD4B')
        ->and($bolepix->externalReferenceId)->toBe('01KP640RNSYXH9G41GR27RTAWP')
        ->and($bolepix->amount)->toBe(150.0)
        ->and($bolepix->dueDate)->toBe('2026-12-30')
        ->and($bolepix->bankSlip?->digitableLine)->toContain('33690')
        ->and($bolepix->bankSlip?->barCode)->toBe('33695969000000123450000003048720009224128213')
        ->and($bolepix->pix?->qrCode)->toContain('BR.GOV.BCB.PIX')
        ->and($bolepix->pix?->reference)->toBe('123e4567-e89b-12d3-a456-426614174000');

    $request = $mocked['requests'][1];

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toContain('/v2/bank_slips')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer test-token')
        ->and($request->getHeaderLine('partner-software-name'))->toBe('Test Suite')
        ->and($request->getHeaderLine('partner-software-version'))->toBe('1.0.0')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(decodedRequestBody($request)['external_reference_id'])->toBe('01KP640RNSYXH9G41GR27RTAWP');
});

it('defaults billing_scheme to the sandbox value when not set', function (): void {
    $mocked = makeBolepixClient([
        new Response(200, [], json_encode(bolepixResponseBody(), JSON_THROW_ON_ERROR)),
    ], Environment::Sandbox);

    $mocked['bolepix']->create(makeCreateBolepixRequest());

    expect(billingSchemeFromRequest($mocked['requests'][1]))->toBe('21');
});

it('defaults billing_scheme to the production value when not set', function (): void {
    $mocked = makeBolepixClient([
        new Response(200, [], json_encode(bolepixResponseBody(), JSON_THROW_ON_ERROR)),
    ], Environment::Production);

    $mocked['bolepix']->create(makeCreateBolepixRequest());

    expect(billingSchemeFromRequest($mocked['requests'][1]))->toBe('15');
});

it('does not override an explicit billing_scheme', function (Environment $environment): void {
    $mocked = makeBolepixClient([
        new Response(200, [], json_encode(bolepixResponseBody(), JSON_THROW_ON_ERROR)),
    ], $environment);

    $request = makeCreateBolepixRequest(new PaymentMethod(
        bankSlip: new BankSlipOptions(billingScheme: '99'),
    ));

    $mocked['bolepix']->create($request);

    expect(billingSchemeFromRequest($mocked['requests'][1]))->toBe('99');
})->with([Environment::Sandbox, Environment::Production]);

it('does not default billing_scheme when the environment is a custom url', function (): void {
    $mocked = makeBolepixClient([
        new Response(200, [], json_encode(bolepixResponseBody(), JSON_THROW_ON_ERROR)),
    ], null);

    $mocked['bolepix']->create(makeCreateBolepixRequest());

    $body = decodedRequestBody($mocked['requests'][1]);

    expect($body)->not->toHaveKey('payment_method');
});

it('parses a response without payment_method', function (): void {
    $mocked = makeBolepixClient([
        new Response(200, [], json_encode([
            'id' => '01HVSBSTN8CCTCTEQT6MC7TD4B',
            'external_reference_id' => '01KP640RNSYXH9G41GR27RTAWP',
            'amount' => 150,
            'due_date' => '2026-12-30',
        ], JSON_THROW_ON_ERROR)),
    ]);

    $bolepix = $mocked['bolepix']->create(makeCreateBolepixRequest());

    expect($bolepix->bankSlip)->toBeNull()
        ->and($bolepix->pix)->toBeNull();
});

it('throws ApiException on http error responses', function (int $status): void {
    $mocked = makeBolepixClient([
        new Response($status, [], json_encode([
            'type' => 'https://developers.c6bank.com.br/v1/error/validation',
            'title' => 'Requisição inválida.',
            'status' => $status,
            'detail' => 'campo obrigatorio ausente',
        ], JSON_THROW_ON_ERROR)),
    ]);

    try {
        $mocked['bolepix']->create(makeCreateBolepixRequest());
    } catch (ApiException $apiException) {
        expect($apiException->statusCode)->toBe($status)
            ->and($apiException->responseBody)->toContain('campo obrigatorio ausente');

        return;
    }

    $this->fail('Expected ApiException was not thrown.');
})->with([400, 401, 422, 500]);

it('throws NetworkException on connection failures', function (): void {
    $mocked = makeMockedHttpClient([
        new Response(200, [], json_encode([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR)),
        new ConnectException('Connection refused', new Request('POST', '/v2/bank_slips')),
    ]);

    $authClient = new AuthClient($mocked['client'], new Credentials('client-id', 'client-secret'));
    $bolepixClient = new BolepixClient($mocked['client'], $authClient, new PartnerSoftware('Test Suite', '1.0.0'));

    $bolepixClient->create(makeCreateBolepixRequest());
})->throws(NetworkException::class);

it('throws MalformedResponseException on invalid json', function (): void {
    $mocked = makeBolepixClient([
        new Response(200, [], 'not-json'),
    ]);

    $mocked['bolepix']->create(makeCreateBolepixRequest());
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException on a non-object json body', function (): void {
    $mocked = makeBolepixClient([
        new Response(200, [], '"5"'),
    ]);

    $mocked['bolepix']->create(makeCreateBolepixRequest());
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when a top-level field is missing', function (string $field): void {
    $body = bolepixResponseBody();
    unset($body[$field]);

    $mocked = makeBolepixClient([
        new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR)),
    ]);

    $mocked['bolepix']->create(makeCreateBolepixRequest());
})->throws(MalformedResponseException::class)->with(['id', 'external_reference_id', 'amount', 'due_date']);

it('throws MalformedResponseException when a bank_slip field is invalid', function (): void {
    $mocked = makeBolepixClient([
        new Response(200, [], json_encode([
            'id' => '01HVSBSTN8CCTCTEQT6MC7TD4B',
            'external_reference_id' => '01KP640RNSYXH9G41GR27RTAWP',
            'amount' => 150,
            'due_date' => '2026-12-30',
            'payment_method' => [
                'bank_slip' => [
                    'originator_id' => '000000304872',
                    'billing_scheme' => '15',
                    'billing_type' => '3',
                    'digitable_line' => '33690.00009   03048.720001   92241.282133   5   96900000012345',
                    'bar_code' => 12345,
                    'our_number' => '0000003048',
                    'number' => '12345678',
                ],
            ],
        ], JSON_THROW_ON_ERROR)),
    ]);

    $mocked['bolepix']->create(makeCreateBolepixRequest());
})->throws(MalformedResponseException::class);

it('throws MalformedResponseException when a pix field is invalid', function (): void {
    $mocked = makeBolepixClient([
        new Response(200, [], json_encode([
            'id' => '01HVSBSTN8CCTCTEQT6MC7TD4B',
            'external_reference_id' => '01KP640RNSYXH9G41GR27RTAWP',
            'amount' => 150,
            'due_date' => '2026-12-30',
            'payment_method' => [
                'pix' => [
                    'qr_code' => '00020126580014BR.GOV.BCB.PIX...6304B14F',
                    'image_content' => 'string',
                    'mime_type' => 'image/png',
                    'reference' => 123456,
                ],
            ],
        ], JSON_THROW_ON_ERROR)),
    ]);

    $mocked['bolepix']->create(makeCreateBolepixRequest());
})->throws(MalformedResponseException::class);
