<?php

declare(strict_types=1);

namespace App\Services\Slack;

final class UrlVerificationResult
{
    private function __construct(
        public readonly string $status, // challenge | bad_request | not_applicable
        public readonly ?string $challenge = null
    ) {
    }

    public function isChallenge(): bool
    {
        return $this->status === 'challenge';
    }
    public function isBadRequest(): bool
    {
        return $this->status === 'bad_request';
    }
    public function isNotApplicable(): bool
    {
        return $this->status === 'not_applicable';
    }

    public function getChallenge(): ?string
    {
        return $this->challenge;
    }

    public static function challenge(string $challenge): self
    {
        return new self('challenge', $challenge);
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
