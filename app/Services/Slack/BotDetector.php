<?php

declare(strict_types=1);

namespace App\Services\Slack;

final class BotDetector
{
    public function isBotEvent(?array $event): bool
    {
        if (!is_array($event)) {
            return false;
        }
        if (!empty($event['bot_id'])) {
            return true;
        }
        if (($event['subtype'] ?? null) === 'bot_message') {
            return true;
        }
        $user = $event['user'] ?? null;
        return is_string($user) && str_starts_with($user, 'B');
    }
}
