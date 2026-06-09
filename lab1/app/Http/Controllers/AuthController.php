<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\UserDTO;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Token;
use App\Models\User;
use App\Services\Token\TokenException;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Контроллер авторизации и регистрации.
 *
 * Отвечает только за обработку HTTP-запросов: валидацию входа делают
 * Form Request'ы, бизнес-логику токенов — TokenService.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly TokenServiceInterface $tokens,
    ) {}

    /**
     * Регистрация нового пользователя.
     *
     * @return JsonResponse данные пользователя (UserDTO), статус 201
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        $user = User::create($dto->toUserAttributes());

        return response()->json(UserDTO::fromModel($user), Response::HTTP_CREATED);
    }

    /**
     * Авторизация по логину и паролю.
     *
     * @return JsonResponse пара токенов + пользователь (AuthSuccessDTO), статус 200;
     *                      либо ошибка 401 при неверных данных
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        $user = User::query()->where('username', $dto->username)->first();

        if ($user === null || ! Hash::check($dto->password, $user->password)) {
            return response()->json(
                ['message' => 'Неверные учётные данные.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $auth = $this->tokens->issuePair($user, $request->ip(), $request->userAgent());

        return response()->json($auth, Response::HTTP_OK);
    }

    /**
     * Данные текущего авторизованного пользователя.
     *
     * @return JsonResponse UserDTO, статус 200
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(UserDTO::fromModel($user), Response::HTTP_OK);
    }

    /**
     * Выход: отзыв текущей пары токенов (access + refresh).
     *
     * @return JsonResponse сообщение об успехе, статус 200
     */
    public function out(Request $request): JsonResponse
    {
        /** @var Token $token */
        $token = $request->attributes->get('access_token');

        $this->tokens->revokePair($token);

        return response()->json(['message' => 'Вы вышли из системы.'], Response::HTTP_OK);
    }

    /**
     * Список активных токенов пользователя (только метаданные).
     *
     * @return JsonResponse TokenListDTO, статус 200
     */
    public function tokens(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($this->tokens->activeTokens($user), Response::HTTP_OK);
    }

    /**
     * Выход со всех устройств: отзыв всех токенов пользователя.
     *
     * @return JsonResponse сообщение об успехе, статус 200
     */
    public function outAll(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->tokens->revokeAllForUser($user);

        return response()->json(['message' => 'Все сессии завершены.'], Response::HTTP_OK);
    }

    /**
     * Обновление пары токенов по refresh-токену.
     *
     * @return JsonResponse новая пара токенов (AuthSuccessDTO), статус 200;
     *                      либо ошибка 401 при недействительном токене
     */
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = (string) $request->input('refresh_token');

        if ($refreshToken === '') {
            return response()->json(
                ['message' => 'Не передан refresh-токен.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $auth = $this->tokens->refresh($refreshToken, $request->ip(), $request->userAgent());
        } catch (TokenException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json($auth, Response::HTTP_OK);
    }

    /**
     * Смена пароля. Текущий пароль и правила нового пароля проверяет
     * ChangePasswordRequest. Из соображений безопасности все активные
     * токены пользователя отзываются.
     *
     * @return JsonResponse сообщение об успехе, статус 200
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        /** @var User $user */
        $user = $request->user();

        $user->forceFill(['password' => $dto->newPassword])->save();
        $this->tokens->revokeAllForUser($user);

        return response()->json(
            ['message' => 'Пароль изменён. Войдите заново.'],
            Response::HTTP_OK,
        );
    }
}
