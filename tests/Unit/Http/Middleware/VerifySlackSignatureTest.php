<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\VerifySlackSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class VerifySlackSignatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Slackの署名シークレットを設定
        config()->set('services.slack.signing_secret', 'test_secret');
    }

    private function runMiddleware(Request $request)
    {
        $middleware = new VerifySlackSignature();

        $next = function (Request $req) {
            return response('OK', 200);
        };

        return $middleware->handle($request, $next(...));
    }

    /**
     * @test
     * @return void
     */
    public function run_対応するパラメータがない時、ステータスコード400を返す(): void
    {
        $request = Request::create('/slack/webhook', 'POST', [], [], [], [], 'body');

        $response = $this->runMiddleware($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Bad Request', $response->getContent());
    }

    /**
     * @test
     * @return void
     */
    public function run_古いタイムスタンプは400を返す(): void
    {
        $body = 'payload';
        $request = Request::create('/slack/webhook', 'POST', [], [], [], [], $body);

        // 10分前の古いタイムスタンプ
        $timestamp = Carbon::now()->subMinutes(10)->timestamp;
        $request->headers->set('X-Slack-Request-Timestamp', (string) $timestamp);

        // 署名自体は正しく作る（が、時間が古いので弾かれる想定）
        $base = "v0:{$timestamp}:{$body}";
        $sig = 'v0=' . hash_hmac('sha256', $base, config('services.slack.signing_secret'));
        $request->headers->set('X-Slack-Signature', $sig);

        $response = $this->runMiddleware($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid timestamp', $response->getContent());
    }

    /**
     * @test
     * @return void
     */
    public function run_署名不一致は401を返す(): void
    {
        $body = 'payload';
        $request = Request::create('/slack/webhook', 'POST', [], [], [], [], $body);

        $timestamp = Carbon::now()->timestamp;
        $request->headers->set('X-Slack-Request-Timestamp', (string) $timestamp);

        // 間違った署名
        $request->headers->set('X-Slack-Signature', 'v0=deadbeef');

        $response = $this->runMiddleware($request);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Signature mismatch', $response->getContent());
    }

    /**
     * @test
     * @return void
     */
    public function run_正しい署名なら通過して200を返す(): void
    {
        $body = '{"type":"url_verification"}';
        $request = Request::create('/slack/webhook', 'POST', [], [], [], [], $body);

        $timestamp = Carbon::now()->timestamp;
        $request->headers->set('X-Slack-Request-Timestamp', (string) $timestamp);

        $base = "v0:{$timestamp}:{$body}";
        $sig = 'v0=' . hash_hmac('sha256', $base, config('services.slack.signing_secret'));
        $request->headers->set('X-Slack-Signature', $sig);

        $response = $this->runMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    /**
     * @test
     * @return void
     */
    public function run_署名シークレット未設定なら500を返す(): void
    {
        config()->set('services.slack.signing_secret', null);

        $body = 'payload';
        $request = Request::create('/slack/webhook', 'POST', [], [], [], [], $body);

        $timestamp = Carbon::now()->timestamp;
        $request->headers->set('X-Slack-Request-Timestamp', (string) $timestamp);

        // 署名は作れないが、ヘッダだけダミー設定
        $request->headers->set('X-Slack-Signature', 'v0=anything');

        $response = $this->runMiddleware($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Server misconfigured', $response->getContent());
    }
}
