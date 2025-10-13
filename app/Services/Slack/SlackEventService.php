<?php

declare(strict_types=1);

namespace App\Services\Slack;

use App\Contracts\Jobs\RelayDispatcher;

final class SlackEventService
{
    public function __construct(
        private readonly BotDetector $botDetector,
        private readonly RelayDispatcher $dispatcher,
    ) {
    }

    public function handleUrlVerification(array $payload): UrlVerificationResult
    {
        if (($payload['type'] ?? null) !== 'url_verification') {
            return UrlVerificationResult::notApplicable();
        }
        $challenge = $payload['challenge'] ?? null;
        if (!is_string($challenge) || $challenge === '') {
            return UrlVerificationResult::badRequest();
        }
        return UrlVerificationResult::challenge($challenge);
    }

    public function shouldIgnoreAsBot(array $payload): bool
    {
        return $this->botDetector->isBotEvent($payload['event'] ?? null);
    }

    public function relayAsync(array $payload): void
    {
        $raw = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $this->dispatcher->dispatch($raw ?: '{}');
    }
}
