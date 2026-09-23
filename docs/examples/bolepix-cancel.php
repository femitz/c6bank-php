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
 * Cancelamento de um bolepix já emitido. Em caso de sucesso a API retorna
 * 204 No Content, então o método não devolve nada.
 */
$externalReferenceId = 'SEU_EXTERNAL_REFERENCE_ID';

try {
    $client->bolepix()->cancel($externalReferenceId);
} catch (ApiException $exception) {
    fwrite(STDERR, "Falha ao cancelar o bolepix: {$exception->getMessage()}\n");
    exit(1);
}

echo "Bolepix {$externalReferenceId} cancelado com sucesso.\n";
