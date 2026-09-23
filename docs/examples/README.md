# Exemplos

Arquivos PHP autocontidos demonstrando cada funcionalidade da biblioteca. Substitua os valores de
exemplo (`seu-client-id`, caminhos de certificado, `SEU_EXTERNAL_REFERENCE_ID`, etc.) pelos seus
dados reais antes de rodar.

- [`authentication.php`](authentication.php) — autenticação (OAuth2 `client_credentials` + mTLS).
- [`bolepix-create.php`](bolepix-create.php) — emissão de bolepix (boleto híbrido com Pix).
- [`bolepix-get.php`](bolepix-get.php) — consulta de um bolepix já emitido.
- [`bolepix-update.php`](bolepix-update.php) — atualização (PATCH) de um bolepix já emitido.
- [`bolepix-pdf.php`](bolepix-pdf.php) — download do PDF do boleto.
- [`bolepix-cancel.php`](bolepix-cancel.php) — cancelamento de um bolepix já emitido.

Para rodar um exemplo:

```bash
php docs/examples/authentication.php
```
