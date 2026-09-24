<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Webhook;

/**
 * Serviços da API do C6 Bank que suportam notificações via webhook.
 */
enum WebhookService: string
{
    case BankSlip = 'BANK_SLIP';
    case BankSlipPix = 'BANK_SLIP_PIX';
    case Checkout = 'CHECKOUT';
}
