<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Femitz\C6BankPhp\Auth\Certificate;
use Femitz\C6BankPhp\Auth\Credentials;
use Femitz\C6BankPhp\Client;
use Femitz\C6BankPhp\Config;
use Femitz\C6BankPhp\Environment;
use Femitz\C6BankPhp\PartnerSoftware;

/**
 * Autenticação (OAuth2 client_credentials + mTLS).
 *
 * O C6 Bank exige um certificado cliente (mTLS) além do client_id/client_secret,
 * tanto no sandbox quanto em produção.
 */
$config = new Config(
    credentials: new Credentials(
        clientId: 'seu-client-id',
        clientSecret: 'seu-client-secret',
    ),
    certificate: new Certificate(
        certPath: '/caminho/para/certificado.crt',
        keyPath: '/caminho/para/chave.key',
        // certPassword: 'opcional, se a chave privada exigir senha',
        // keyPassword: 'opcional, se a chave privada exigir senha',
    ),
    partnerSoftware: new PartnerSoftware(
        name: 'Nome do seu software',
        version: '1.0.0',
    ),
    environment: Environment::Sandbox, // ou Environment::Production
);

$client = new Client($config);

// O token é obtido na primeira chamada e reaproveitado em memória
// (na mesma instância de Client) até expirar.
$token = $client->getAccessToken();

echo $token->authorizationHeader().PHP_EOL; // "Bearer eyJ..."
echo 'Expira em: '.$token->expiresAt->format('Y-m-d H:i:s').PHP_EOL;
