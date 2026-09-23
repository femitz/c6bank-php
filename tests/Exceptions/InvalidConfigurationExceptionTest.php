<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Exceptions\InvalidConfigurationException;

it('builds a message for an empty field', function (): void {
    $exception = InvalidConfigurationException::forEmptyField('client_id');

    expect($exception->getMessage())->toBe('O campo "client_id" não pode ser vazio.');
});
