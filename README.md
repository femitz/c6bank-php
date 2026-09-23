<p align="center">
    <p align="center">
        <a href="https://github.com/femitz/c6bank-php/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/femitz/c6bank-php/actions/workflows/tests.yml/badge.svg"></a>
        <a href="https://packagist.org/packages/femitz/c6bank-php"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/femitz/c6bank-php"></a>
        <a href="https://packagist.org/packages/femitz/c6bank-php"><img alt="Latest Version" src="https://img.shields.io/packagist/v/femitz/c6bank-php"></a>
        <a href="https://packagist.org/packages/femitz/c6bank-php"><img alt="License" src="https://img.shields.io/packagist/l/femitz/c6bank-php"></a>
    </p>
</p>

------

# C6 Bank PHP

Uma biblioteca PHP para facilitar a integração com a **API do C6 Bank**, permitindo consumir seus endpoints (como Pix, boletos, extratos e demais recursos disponibilizados pelo banco) de forma simples e tipada, sem precisar lidar diretamente com autenticação, requisições HTTP e parsing de respostas.

> **Requer [PHP 8.5+](https://php.net/releases/)**

## 📦 Instalação

Instale via [Composer](https://getcomposer.org):

```bash
composer require femitz/c6bank-php
```

## 🚀 Uso

### Autenticação

A API do C6 Bank (BaaS) utiliza o fluxo OAuth2 `client_credentials` combinado com **mTLS**
(certificado cliente + chave privada), tanto no sandbox quanto — presumivelmente — em produção.

```php
use Femitz\C6BankPhp\Client;
use Femitz\C6BankPhp\Config;
use Femitz\C6BankPhp\Environment;
use Femitz\C6BankPhp\PartnerSoftware;
use Femitz\C6BankPhp\Auth\Certificate;
use Femitz\C6BankPhp\Auth\Credentials;

$config = new Config(
    credentials: new Credentials(
        clientId: 'seu-client-id',
        clientSecret: 'seu-client-secret',
    ),
    certificate: new Certificate(
        certPath: '/caminho/para/certificado.crt',
        keyPath: '/caminho/para/chave.key',
        // certPassword: 'opcional',
        // keyPassword: 'opcional',
    ),
    partnerSoftware: new PartnerSoftware(
        name: 'Nome do seu software',
        version: '1.0.0',
    ),
    environment: Environment::Sandbox, // ou Environment::Production
);

$client = new Client($config);

$token = $client->getAccessToken(); // reaproveitado em memória até expirar

echo $token->authorizationHeader(); // "Bearer eyJ..."
```

### Bolepix (emissão de boleto híbrido com Pix)

```php
use Femitz\C6BankPhp\Bolepix\Address;
use Femitz\C6BankPhp\Bolepix\CreateBolepixRequest;
use Femitz\C6BankPhp\Bolepix\Fees;
use Femitz\C6BankPhp\Bolepix\Payer;
use Femitz\C6BankPhp\Bolepix\PaymentMethod;
use Femitz\C6BankPhp\Bolepix\PixOptions;

$request = new CreateBolepixRequest(
    externalReferenceId: 'seu-id-de-referencia',
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
        pix: new PixOptions(
            key: '123e4567-e89b-12d3-a456-426614174000',
            type: 'EVP',
        ),
    ),
);

$bolepix = $client->bolepix()->create($request);

echo $bolepix->bankSlip?->digitableLine;
echo $bolepix->pix?->qrCode;
```

Para consultar um bolepix já emitido, use o mesmo `external_reference_id` informado na criação:

```php
$bolepix = $client->bolepix()->get('seu-id-de-referencia');

echo $bolepix->status;
echo $bolepix->payer?->name;
```

Para baixar o PDF do boleto:

```php
$pdf = $client->bolepix()->getPdf('seu-id-de-referencia');

file_put_contents('boleto.pdf', $pdf);
```

Para atualizar um bolepix já emitido (apenas os campos informados são enviados; note que, diferente da
emissão, o `payer` aqui só aceita `email` e `address` — `name`/`tax_id` não podem ser alterados):

```php
use Femitz\C6BankPhp\Bolepix\BankSlipOptions;
use Femitz\C6BankPhp\Bolepix\UpdateBolepixRequest;
use Femitz\C6BankPhp\Bolepix\UpdatePayerOptions;

$request = new UpdateBolepixRequest(
    amount: 150.00,
    dueDate: '2026-12-30',
    description: 'Mensalidade referente a Junho/2026',
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
            instructions: ['Não receber após o vencimento'],
        ),
    ),
    origin: 'e-commerce',
);

$bolepix = $client->bolepix()->update('seu-id-de-referencia', $request);
```

Para cancelar um bolepix já emitido:

```php
$client->bolepix()->cancel('seu-id-de-referencia');
```

> Documentação de uso detalhada será adicionada conforme os demais recursos da API
> (Pix, extratos, etc.) forem implementados.

## 🧪 Desenvolvimento

🧹 Mantenha o código padronizado com **Pint**:
```bash
composer lint
```

✅ Rode refatorações com o **Rector**:
```bash
composer refactor
```

⚗️ Rode a análise estática com o **PHPStan**:
```bash
composer test:types
```

✅ Rode os testes unitários com o **Pest**:
```bash
composer test:unit
```

🚀 Rode toda a suíte de testes:
```bash
composer test
```

## 🤝 Contribuindo

Contribuições são bem-vindas! Veja o arquivo [CONTRIBUTING.md](CONTRIBUTING.md) para mais detalhes.

## 📄 Licença

Este projeto está licenciado sob a **[MIT license](https://opensource.org/licenses/MIT)**.

---

Criado e mantido por **[Felipe Schmitz](mailto:schmitzdz14@gmail.com)**.
