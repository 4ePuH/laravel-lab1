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
 * Самописный middleware проверки access-токена.
 *
 * Берёт токен из заголовка Authorization: Bearer <token>,
 * проверяет его через сервис и кладёт пользователя и запись токена
 * в запрос, чтобы контроллер мог ими пользоваться.
 */
final class AuthenticateToken
{
    public function __construct(
        private readonly TokenServiceInterface $tokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if ($bearer === null) {
            return $this->unauthorized('Токен доступа не передан.');
        }

        try {
            $token = $this->tokens->authenticateAccessToken($bearer);
        } catch (TokenException $e) {
            return $this->unauthorized($e->getMessage());
        }

        // Делаем пользователя доступным через $request->user().
        $request->setUserResolver(static fn () => $token->user);
        // Запись токена пригодится, например, для out (отзыв текущей пары).
        $request->attributes->set('access_token', $token);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return new JsonResponse(['message' => $message], Response::HTTP_UNAUTHORIZED);
    }
}
