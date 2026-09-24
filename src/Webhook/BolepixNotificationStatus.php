<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Webhook;

/**
 * Status possíveis de uma notificação de bolepix recebida via webhook.
 */
enum BolepixNotificationStatus: string
{
    case Created = 'CREATED';
    case Paid = 'PAID';
    case WaitingConfirmation = 'WAITING_CONFIRMATION';
}
