<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Таблица для отслеживания активных токенов.
     *
     * ВАЖНО: сам токен в открытом виде здесь НЕ хранится.
     * Храним только:
     *  - jti       — случайный идентификатор токена (зашит в сам токен);
     *  - token_hash — SHA-256 хеш токена (для обнаружения повторного использования);
     *  - pair_id   — связывает пару access + refresh, выданную вместе.
     */
    public function up(): void
    {
        Schema::create('tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('pair_id')->index();           // связь access <-> refresh
            $table->string('jti')->unique();            // идентификатор токена
            $table->string('type', 10);                 // 'access' или 'refresh'
            $table->string('token_hash', 64)->index();  // SHA-256 токена
            $table->timestamp('expires_at');            // когда истекает
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
