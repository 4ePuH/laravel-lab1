<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Token\TokenException;
use App\Services\Token\TokenServiceInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пропускает только НЕавторизованных. Если пришёл действительный
 * access-токен — доступ запрещён (нужно для маршрута регистрации).
 */
final class RejectAuthenticated
{
    public function __construct(
        private readonly TokenServiceInterface $tokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if ($bearer !== null) {
            try {
                $this->tokens->authenticateAccessToken($bearer);

                // Токен валиден — значит пользователь уже авторизован.
                return new JsonResponse(
                    ['message' => 'Уже авторизованы.'],
                    Response::HTTP_FORBIDDEN,
                );
            } catch (TokenException) {
                // Недействительный токен трактуем как «не авторизован» — пропускаем.
            }
        }

        return $next($request);
    }
}
