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
 * Download do PDF do boleto de um bolepix já emitido. O método retorna os
 * bytes crus do arquivo.
 */
$externalReferenceId = 'SEU_EXTERNAL_REFERENCE_ID';

try {
    $pdf = $client->bolepix()->getPdf($externalReferenceId);
} catch (ApiException $exception) {
    fwrite(STDERR, "Falha ao baixar o PDF: {$exception->getMessage()}\n");
    exit(1);
}

$outputPath = __DIR__.'/boleto.pdf';

file_put_contents($outputPath, $pdf);

echo "PDF salvo em {$outputPath} (".strlen($pdf)." bytes)\n";
