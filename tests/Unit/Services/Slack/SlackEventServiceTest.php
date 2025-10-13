<?php

namespace Tests\Unit\Services\Slack;

use App\Contracts\Jobs\RelayDispatcher;
use App\Services\Slack\BotDetector;
use App\Services\Slack\SlackEventService;
use Tests\TestCase;

class SlackEventServiceTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideUrlVerificationPayloads
     * @param array $payload
     * @param array{challenge:bool,bad_request:bool,not_applicable:bool,challenge_value:?string} $expected
     * @return void
     */
    public function handleUrlVerification_全条件分岐を網羅して期待通り判定できること(array $payload, array $expected): void
    {
        $service = new SlackEventService(
            new BotDetector(),
            \Mockery::mock(RelayDispatcher::class)
        );

        $result = $service->handleUrlVerification($payload);

        $this->assertSame($expected['challenge'], $result->isChallenge());
        $this->assertSame($expected['bad_request'], $result->isBadRequest());
        $this->assertSame($expected['not_applicable'], $result->isNotApplicable());
        $this->assertSame($expected['challenge_value'], $result->getChallenge());
    }

    /**
     * @return array<string, array{0: array, 1: array{challenge:bool,bad_request:bool,not_applicable:bool,challenge_value:?string}}>
     */
    public static function provideUrlVerificationPayloads(): array
    {
        return [
            'typeが未設定' => [
                [],
                ['challenge' => false, 'bad_request' => false, 'not_applicable' => true, 'challenge_value' => null],
            ],
            'typeがurl_verification以外' => [
                ['type' => 'event_callback'],
                ['challenge' => false, 'bad_request' => false, 'not_applicable' => true, 'challenge_value' => null],
            ],
            'challenge未設定' => [
                ['type' => 'url_verification'],
                ['challenge' => false, 'bad_request' => true, 'not_applicable' => false, 'challenge_value' => null],
            ],
            'challengeが空文字' => [
                ['type' => 'url_verification', 'challenge' => ''],
                ['challenge' => false, 'bad_request' => true, 'not_applicable' => false, 'challenge_value' => null],
            ],
            'challengeが文字列以外' => [
                ['type' => 'url_verification', 'challenge' => 123],
                ['challenge' => false, 'bad_request' => true, 'not_applicable' => false, 'challenge_value' => null],
            ],
            '正常' => [
                ['type' => 'url_verification', 'challenge' => 'challenge-xyz'],
                ['challenge' => true, 'bad_request' => false, 'not_applicable' => false, 'challenge_value' => 'challenge-xyz'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideBotPayloads
     * @param array $payload
     * @param bool $expected
     * @return void
     */
    public function shouldIgnoreAsBot_Bot検知の結果を返すこと(array $payload, bool $expected): void
    {
        $service = new SlackEventService(
            new BotDetector(),
            \Mockery::mock(RelayDispatcher::class)
        );

        $this->assertSame($expected, $service->shouldIgnoreAsBot($payload));
    }

    /**
     * @return array<string, array{0: array, 1: bool}>
     */
    public static function provideBotPayloads(): array
    {
        return [
            'event未設定' => [[], false],
            'bot_idあり' => [['event' => ['bot_id' => 'B123']], true],
            'subtypeがbot_message' => [['event' => ['subtype' => 'bot_message']], true],
            'userがBで始まる' => [['event' => ['user' => 'BABCDE']], true],
            'userが通常ユーザー' => [['event' => ['user' => 'U12345']], false],
            'userが文字列以外' => [['event' => ['user' => 100]], false],
            'subtypeがその他' => [['event' => ['subtype' => 'message']], false],
        ];
    }

    /**
     * @test
     */
    public function relayAsync_UnicodeをエスケープせずにJSON文字列を送ること(): void
    {
        $payload = [
            'text' => '日本語',
            'nested' => ['emoji' => '😄'],
        ];

        $dispatcher = \Mockery::mock(RelayDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with('{"text":"日本語","nested":{"emoji":"😄"}}');

        $service = new SlackEventService(new BotDetector(), $dispatcher);
        $service->relayAsync($payload);
    }

    /**
     * @test
     */
    public function relayAsync_JSONエンコード失敗時は空オブジェクト文字列を送ること(): void
    {
        // 循環参照を含む配列を作成して json_encode を失敗させる
        $payload = [];
        $payload['self'] = &$payload;

        $dispatcher = \Mockery::mock(RelayDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with('{}');

        $service = new SlackEventService(new BotDetector(), $dispatcher);
        $service->relayAsync($payload);
    }
}
