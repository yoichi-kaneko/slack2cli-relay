<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\SlacksController;
use App\Jobs\RelayCli;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class SlacksControllerTest extends TestCase
{
    private function makeJsonRequest(array $payload): Request
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        return Request::create(
            '/slack/events',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body
        );
    }

    private function runController(Request $request)
    {
        $controller = new SlacksController();

        return $controller->events($request);
    }

    /**
     * @test
     * @return void
     */
    public function events_url_verification_正しいchallengeなら200とテキストを返す(): void
    {
        $challenge = 'test_challenge';
        $request = $this->makeJsonRequest([
            'type' => 'url_verification',
            'challenge' => $challenge,
        ]);

        $response = $this->runController($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($challenge, $response->getContent());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
    }

    /**
     * @test
     * @return void
     */
    public function events_url_verification_challengeが無い時は400を返す(): void
    {
        $request = $this->makeJsonRequest([
            'type' => 'url_verification',
            // challenge なし
        ]);

        $response = $this->runController($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Bad Request', $response->getContent());
    }

    /**
     * @test
     * @return void
     */
    public function events_その他のタイプはRelayCliがキュー投入され、200でok_trueを返す(): void
    {
        Bus::fake();

        $payload = [
            'type' => 'event_callback',
            'event' => ['type' => 'app_mention', 'text' => 'hi'],
        ];
        $request = $this->makeJsonRequest($payload);

        $response = $this->runController($request);

        Bus::assertDispatched(RelayCli::class, function (RelayCli $job) use ($payload) {
            // コンストラクタ引数のペイロード文字列を検証
            // リクエストボディがそのまま入る仕様に合わせる
            $expectedRaw = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $ref = new \ReflectionClass($job);
            $prop = $ref->getProperty('payload');
            return $prop->getValue($job) === $expectedRaw;
        });
        $this->assertSame(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertSame(['ok' => true], json_decode($response->getContent(), true));
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }
}
