<?php

declare(strict_types=1);

namespace Femitz\C6BankPhp\Auth;

use DateTimeImmutable;

/**
 * Token de acesso retornado pelo endpoint de autenticação do C6 Bank.
 */
final readonly class AccessToken
{
    private const int DEFAULT_SAFETY_MARGIN_SECONDS = 30;

    public function __construct(
        public string $token,
        public string $type,
        public DateTimeImmutable $expiresAt,
        public ?string $scope = null,
    ) {}

    public static function fromExpiresIn(string $token, string $type, int $expiresIn, ?string $scope = null): self
    {
        return new self(
            token: $token,
            type: $type,
            expiresAt: (new DateTimeImmutable)->modify(sprintf('%+d seconds', $expiresIn)),
            scope: $scope,
        );
    }

    public function isExpired(int $safetyMarginSeconds = self::DEFAULT_SAFETY_MARGIN_SECONDS): bool
    {
        $threshold = (new DateTimeImmutable)->modify(sprintf('+%d seconds', $safetyMarginSeconds));

        return $this->expiresAt <= $threshold;
    }

    public function authorizationHeader(): string
    {
        return sprintf('%s %s', $this->type, $this->token);
    }
}
