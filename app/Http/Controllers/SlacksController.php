<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\RelayCli;
use App\Services\Slack\SlackEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * SlackからのEvent API通知を受信するコントローラー
 */
class SlacksController extends Controller
{
    public function __construct(
        private readonly SlackEventService $service,
    ) {
    }

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
        $verify = $this->service->handleUrlVerification($payload);
        if ($verify->isChallenge()) {
            return response($verify->getChallenge(), 200)->header('Content-Type', 'text/plain');
        }
        if ($verify->isBadRequest()) {
            return response('Bad Request', 400);
        }

        // botからの通知はループ防止のため処理せず、即200を返却する
        if ($this->service->shouldIgnoreAsBot($payload)) {
            return new JsonResponse(['ok' => true], 200);
        }

        // それ以外はリレイを非同期実行（Serviceへ委譲）
        $this->service->relayAsync($payload);

        return new JsonResponse(['ok' => true], 200);
    }
}
