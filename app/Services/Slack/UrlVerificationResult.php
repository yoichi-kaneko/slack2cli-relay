<?php

declare(strict_types=1);

namespace App\Services\Slack;

final class UrlVerificationResult
{
    private function __construct(
        public readonly string $status, // ok | bad_request | not_applicable
        public readonly ?string $challenge = null
    ) {
    }

    public function isOk(): bool
    {
        return $this->status === 'ok';
    }
    public function isBadRequest(): bool
    {
        return $this->status === 'bad_request';
    }
    public function isNotApplicable(): bool
    {
        return $this->status === 'not_applicable';
    }

    public static function ok(string $challenge): self
    {
        return new self('ok', $challenge);
    }
    public static function badRequest(): self
    {
        return new self('bad_request');
    }
    public static function notApplicable(): self
    {
        return new self('not_applicable');
    }
}
