<?php

declare(strict_types=1);

namespace App\Services\Token;

use App\DTO\AuthSuccessDTO;
use App\DTO\TokenListDTO;
use App\Models\Token;
use App\Models\User;

/**
 * Контракт сервиса токенов. Контроллер и middleware зависят
 * от этой абстракции, а не от конкретной реализации (DIP).
 */
interface TokenServiceInterface
{
    /**
     * Выдаёт новую пару токенов (access + refresh) с учётом лимита.
     */
    public function issuePair(User $user, ?string $ip, ?string $userAgent): AuthSuccessDTO;

    /**
     * Проверяет access-токен и возвращает его запись из БД.
     *
     * @throws TokenException если токен недействителен / истёк / отозван
     */
    public function authenticateAccessToken(string $accessToken): Token;

    /**
     * Обменивает действительный refresh-токен на новую пару (ротация).
     *
     * @throws TokenException если токен недействителен или уже использован
     */
    public function refresh(string $refreshToken, ?string $ip, ?string $userAgent): AuthSuccessDTO;

    /**
     * Отзывает конкретную пару токенов (текущий выход — out).
     */
    public function revokePair(Token $token): void;

    /**
     * Отзывает все токены пользователя (выход со всех устройств — out_all).
     */
    public function revokeAllForUser(User $user): void;

    /**
     * Список активных токенов пользователя (только метаданные).
     */
    public function activeTokens(User $user): TokenListDTO;
}
