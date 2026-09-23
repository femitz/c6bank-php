<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Femitz\C6BankPhp\Auth\Certificate;
use Femitz\C6BankPhp\Auth\Credentials;
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
 * Consulta de um bolepix já emitido, pelo mesmo `external_reference_id`
 * informado na emissão.
 */
$externalReferenceId = 'SEU_EXTERNAL_REFERENCE_ID';

try {
    $bolepix = $client->bolepix()->get($externalReferenceId);
} catch (ApiException $exception) {
    fwrite(STDERR, "Falha ao consultar o bolepix: {$exception->getMessage()}\n");
    exit(1);
}

echo "Status: {$bolepix->status}\n";
echo "Valor: R$ {$bolepix->amount}\n";
echo "Vencimento: {$bolepix->dueDate}\n";
echo 'Pagador: '.$bolepix->payer?->name."\n";
echo 'Linha digitável: '.$bolepix->bankSlip?->digitableLine."\n";
