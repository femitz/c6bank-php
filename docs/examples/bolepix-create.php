<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Femitz\C6BankPhp\Auth\Certificate;
use Femitz\C6BankPhp\Auth\Credentials;
use Femitz\C6BankPhp\Bolepix\Address;
use Femitz\C6BankPhp\Bolepix\CreateBolepixRequest;
use Femitz\C6BankPhp\Bolepix\Fees;
use Femitz\C6BankPhp\Bolepix\Payer;
use Femitz\C6BankPhp\Bolepix\PaymentMethod;
use Femitz\C6BankPhp\Bolepix\PixOptions;
use Femitz\C6BankPhp\Client;
use Femitz\C6BankPhp\Config;
use Femitz\C6BankPhp\Environment;
use Femitz\C6BankPhp\Exceptions\ApiException;
use Femitz\C6BankPhp\PartnerSoftware;

$config = new Config(
    credentials: new Credentials('seu-client-id', 'seu-client-secret'),
    certificate: new Certificate('/caminho/para/certificado.crt', '/caminho/para/chave.key'),
    partnerSoftware: new PartnerSoftware('Nome do seu software', '1.0.0'),
    environment: Environment::Sandbox,
);

$client = new Client($config);

/**
 * Emissão de bolepix (boleto híbrido com Pix).
 *
 * `external_reference_id` é um identificador único, de exatamente 26
 * caracteres alfanuméricos maiúsculos (ex.: um ULID), atribuído por você.
 * É usado depois em todas as operações de consulta/atualização/cancelamento.
 */
$externalReferenceId = strtoupper(bin2hex(random_bytes(13)));

$request = new CreateBolepixRequest(
    externalReferenceId: $externalReferenceId,
    amount: 150.00,
    dueDate: '2026-12-30', // ou uma instância de DateTimeInterface
    payer: new Payer(
        name: 'José da Silva',
        taxId: '12345678910',
        address: new Address(
            address: 'Av. Nove de Julho, 3186',
            neighborhood: 'Jardim Paulista',
            city: 'São Paulo',
            state: 'SP',
            zipCode: '01406000',
        ),
        email: 'pagador@email.com.br',
    ),
    description: 'Mensalidade referente a Junho/2026',
    daysAfterDueDate: 10,
    fees: new Fees(
        fineValue: 10,
        fineDeadline: 1,
        fineType: 'FIXED_VALUE',
        interestValue: 0.33,
        interestDeadline: 1,
        interestType: 'VALUE_PER_DAY',
    ),
    paymentMethod: new PaymentMethod(
        // A chave Pix precisa já estar cadastrada na conta do sandbox/produção.
        // type: 'CPF' | 'CNPJ' | 'EMAIL' | 'PHONE' | 'EVP' (chave aleatória).
        pix: new PixOptions(
            key: 'sua-chave-pix',
            type: 'EVP',
        ),
    ),
    origin: 'e-commerce',
);

try {
    $bolepix = $client->bolepix()->create($request);
} catch (ApiException $exception) {
    fwrite(STDERR, "Falha ao emitir o bolepix: {$exception->getMessage()}\n");
    exit(1);
}

echo "ID: {$bolepix->id}\n";
echo "Status: {$bolepix->status}\n";
echo 'Linha digitável: '.$bolepix->bankSlip?->digitableLine."\n";
echo 'QR Code Pix: '.$bolepix->pix?->qrCode."\n";
