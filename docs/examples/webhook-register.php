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
use Femitz\C6BankPhp\Webhook\RegisterWebhookRequest;

$config = new Config(
    credentials: new Credentials('seu-client-id', 'seu-client-secret'),
    certificate: new Certificate('/caminho/para/certificado.crt', '/caminho/para/chave.key'),
    partnerSoftware: new PartnerSoftware('Nome do seu software', '1.0.0'),
    environment: Environment::Sandbox,
);

$client = new Client($config);

/**
 * Registra um webhook para receber notificações de bolepix (`BANK_SLIP`).
 */
$request = new RegisterWebhookRequest(
    url: 'https://www.meuendereco.com.br/webhook/xpto',
);

try {
    $webhook = $client->webhook()->register($request);
} catch (ApiException $exception) {
    fwrite(STDERR, "Falha ao registrar o webhook: {$exception->getMessage()}\n");
    exit(1);
}

echo "Webhook registrado para o client {$webhook->clientId} em {$webhook->createdAt}.\n";
