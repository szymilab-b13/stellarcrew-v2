<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

interface RefreshTokenGenerator
{
    /**
     * Generates a cryptographically-random plaintext refresh token (handed to the worker once).
     */
    public function generate(): string;

    /**
     * Hashes a plaintext refresh token to the sha256 hex representation stored server-side.
     */
    public function hash(string $plaintext): string;
}
