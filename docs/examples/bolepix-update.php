<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Femitz\C6BankPhp\Auth\Certificate;
use Femitz\C6BankPhp\Auth\Credentials;
use Femitz\C6BankPhp\Bolepix\BankSlipOptions;
use Femitz\C6BankPhp\Bolepix\Fees;
use Femitz\C6BankPhp\Bolepix\PaymentMethod;
use Femitz\C6BankPhp\Bolepix\UpdateBolepixRequest;
use Femitz\C6BankPhp\Bolepix\UpdatePayerOptions;
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

$externalReferenceId = 'SEU_EXTERNAL_REFERENCE_ID';

/**
 * Atualização (PATCH) de um bolepix já emitido.
 *
 * Apenas os campos informados aqui são enviados à API — os demais
 * permanecem inalterados. Note que, diferente da emissão, o `payer` aqui só
 * aceita `email` e `address`; `name`/`tax_id` não podem ser alterados após
 * a emissão.
 */
$request = new UpdateBolepixRequest(
    amount: 150.00,
    dueDate: '2026-12-30',
    description: 'Mensalidade referente a Junho/2026 (atualizada)',
    daysAfterDueDate: 30,
    payer: new UpdatePayerOptions(
        email: 'novo-email@email.com.br',
    ),
    fees: new Fees(
        fineValue: 10,
        fineType: 'FIXED_VALUE',
    ),
    paymentMethod: new PaymentMethod(
        bankSlip: new BankSlipOptions(
            yourNumber: '0000003048',
            instructions: ['Não receber após o vencimento', 'Multa de 2% após vencimento'],
        ),
    ),
    origin: 'e-commerce',
);

try {
    $bolepix = $client->bolepix()->update($externalReferenceId, $request);
} catch (ApiException $exception) {
    fwrite(STDERR, "Falha ao atualizar o bolepix: {$exception->getMessage()}\n");
    exit(1);
}

echo "Status: {$bolepix->status}\n";
echo "Descrição: {$bolepix->description}\n";
