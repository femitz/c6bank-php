<?php

declare(strict_types=1);

use Femitz\C6BankPhp\Auth\AccessToken;

it('computes expiresAt from expiresIn', function (): void {
    $before = new DateTimeImmutable;

    $token = AccessToken::fromExpiresIn('abc', 'Bearer', 3600);

    $after = new DateTimeImmutable;

    expect($token->expiresAt)->toBeGreaterThanOrEqual($before->modify('+3599 seconds'))
        ->and($token->expiresAt)->toBeLessThanOrEqual($after->modify('+3601 seconds'));
});

it('is not expired when far in the future', function (): void {
    $token = AccessToken::fromExpiresIn('abc', 'Bearer', 3600);

    expect($token->isExpired())->toBeFalse();
});

it('is expired when expiresIn is negative', function (): void {
    $token = AccessToken::fromExpiresIn('abc', 'Bearer', -1);

    expect($token->isExpired())->toBeTrue();
});

it('is expired within the safety margin', function (): void {
    $token = AccessToken::fromExpiresIn('abc', 'Bearer', 10);

    expect($token->isExpired(safetyMarginSeconds: 30))->toBeTrue();
});

it('exposes the scope when present', function (): void {
    $token = AccessToken::fromExpiresIn('abc', 'Bearer', 3600, 'pix.read');

    expect($token->scope)->toBe('pix.read');
});

it('has a null scope by default', function (): void {
    $token = AccessToken::fromExpiresIn('abc', 'Bearer', 3600);

    expect($token->scope)->toBeNull();
});

it('builds the authorization header', function (): void {
    $token = AccessToken::fromExpiresIn('abc123', 'Bearer', 3600);

    expect($token->authorizationHeader())->toBe('Bearer abc123');
});
