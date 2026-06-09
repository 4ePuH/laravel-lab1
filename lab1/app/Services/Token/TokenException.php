<?php

declare(strict_types=1);

namespace App\Services\Token;

use RuntimeException;

/**
 * Ошибка работы с токеном (недействителен, истёк, отозван и т.п.).
 */
final class TokenException extends RuntimeException {}
