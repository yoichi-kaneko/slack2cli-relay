<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Slackからの通知のSignatureを検知する
 */
class VerifySlackSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');

        if (!$timestamp || !$signature) {
            return response('Bad Request', 400);
        }

        // 5分より古い/未来は拒否（リプレイ攻撃対策）
        if (abs(now()->timestamp - (int) $timestamp) > 300) {
            return response('Invalid timestamp', 400);
        }

        // 生のボディを取得（再エンコード禁止）
        $rawBody = $request->getContent();

        $basestring = 'v0:' . $timestamp . ':' . $rawBody;
        $secret = config('services.slack.signing_secret');

        if (!$secret) {
            return response('Server misconfigured', 500);
        }

        $hash = 'v0=' . hash_hmac('sha256', $basestring, $secret);

        // タイミング安全な比較
        if (!hash_equals($hash, $signature)) {
            return response('Signature mismatch', 401);
        }

        return $next($request);
    }
}
