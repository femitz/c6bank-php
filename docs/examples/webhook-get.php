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
use Femitz\C6BankPhp\Webhook\WebhookService;

$config = new Config(
    credentials: new Credentials('seu-client-id', 'seu-client-secret'),
    certificate: new Certificate('/caminho/para/certificado.crt', '/caminho/para/chave.key'),
    partnerSoftware: new PartnerSoftware('Nome do seu software', '1.0.0'),
    environment: Environment::Sandbox,
);

$client = new Client($config);

/**
 * Consulta o webhook registrado para o serviço `BANK_SLIP`.
 */
try {
    $webhook = $client->webhook()->get(WebhookService::BankSlip);
} catch (ApiException $exception) {
    fwrite(STDERR, "Falha ao consultar o webhook: {$exception->getMessage()}\n");
    exit(1);
}

echo "Webhook do client {$webhook->clientId} aponta para {$webhook->url}.\n";
