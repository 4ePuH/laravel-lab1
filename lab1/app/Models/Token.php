<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Запись об активном токене (access или refresh).
 *
 * @property int $id
 * @property int $user_id
 * @property string $pair_id
 * @property string $jti
 * @property string $type
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $last_used_at
 * @property Carbon|null $revoked_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class Token extends Model
{
    /** Тип токена доступа. */
    public const string TYPE_ACCESS = 'access';

    /** Тип токена обновления. */
    public const string TYPE_REFRESH = 'refresh';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'pair_id',
        'jti',
        'type',
        'token_hash',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'ip_address',
        'user_agent',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** Пользователь — владелец токена. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Скоуп: только «живые» токены — не отозванные и не истёкшие.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }
}
