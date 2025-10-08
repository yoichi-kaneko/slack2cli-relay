<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\SlacksController;
use Illuminate\Http\Request;
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
    public function events_その他のタイプは200でok_trueを返す(): void
    {
        $request = $this->makeJsonRequest([
            'type' => 'event_callback',
            'event' => ['type' => 'app_mention'],
        ]);

        $response = $this->runController($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertSame(['ok' => true], json_decode($response->getContent(), true));
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }

    /**
     * @test
     * @return void
     */
    public function events_type未指定でも200でok_trueを返す(): void
    {
        $request = $this->makeJsonRequest([
            // type 未指定
        ]);

        $response = $this->runController($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertSame(['ok' => true], json_decode($response->getContent(), true));
    }
}
