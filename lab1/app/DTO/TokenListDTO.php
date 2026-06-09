<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Token;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * Неизменяемый список активных токенов пользователя.
 */
final class TokenListDTO implements Arrayable, JsonSerializable
{
    /**
     * @param  list<TokenInfoDTO>  $tokens
     */
    public function __construct(
        public readonly array $tokens,
    ) {}

    /**
     * Собирает список из коллекции моделей Token.
     *
     * @param  Collection<int, Token>  $tokens
     */
    public static function fromCollection(Collection $tokens): self
    {
        return new self(
            $tokens->map(static fn (Token $token): TokenInfoDTO => TokenInfoDTO::fromModel($token))
                ->values()
                ->all(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tokens' => array_map(
                static fn (TokenInfoDTO $token): array => $token->toArray(),
                $this->tokens,
            ),
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
