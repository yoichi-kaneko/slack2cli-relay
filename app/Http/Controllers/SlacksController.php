<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * SlackからのEvent API通知を受信するコントローラー
 */
class SlacksController extends Controller
{
    /**
     *
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function events(Request $request): Response|JsonResponse
    {
        // 受信JSONを配列として取得
        $payload = $request->json()->all();

        // typeがurl_verificationならchallengeをそのまま返す
        if (($payload['type'] ?? null) === 'url_verification') {
            $challenge = $payload['challenge'] ?? null;

            // challengeが無い/不正なら400
            if (!is_string($challenge) || $challenge === '') {
                return response('Bad Request', 400);
            }

            // Slack仕様に従い、challenge文字列をそのまま返却（Content-Type: text/plain）
            return response($challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        // それ以外は、Slackのリトライ防止のため即座に200を返却
        // まだ実処理は行わない
        return new JsonResponse(['ok' => true], 200);
    }
}
