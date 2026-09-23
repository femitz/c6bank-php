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

> Documentação de uso detalhada será adicionada conforme os recursos da API forem implementados.

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
