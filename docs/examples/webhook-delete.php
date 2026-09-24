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
 * Remove o webhook registrado para o serviço `BANK_SLIP`. Em caso de
 * sucesso a API retorna 204 No Content, então o método não devolve nada.
 */
try {
    $client->webhook()->delete(WebhookService::BankSlip);
} catch (ApiException $exception) {
    fwrite(STDERR, "Falha ao remover o webhook: {$exception->getMessage()}\n");
    exit(1);
}

echo "Webhook removido com sucesso.\n";
