<?php

namespace Tests\Unit\Services\Slack;

use App\Services\Slack\BotDetector;
use Tests\TestCase;

class BotDetectorTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideEvents
     * @param $event
     * @param bool $expected
     * @return void
     */
    public function isBotEvent_全条件分岐を網羅して期待通り判定できること($event, bool $expected): void
    {
        $detector = new BotDetector();

        $this->assertSame($expected, $detector->isBotEvent($event));
    }

    /**
     * @return array<string, array{0:mixed,1:bool}>
     */
    public static function provideEvents(): array
    {
        return [
            'eventがnull' => [null, false],
            '空配列' => [[], false],
            'bot_idあり' => [['bot_id' => 'B123'], true],
            'subtypeがbot_message' => [['subtype' => 'bot_message'], true],
            'userがBで始まる' => [['user' => 'BABCDE'], true],
            'userが通常ユーザー' => [['user' => 'U12345'], false],
            'userが文字列以外' => [['user' => 100], false],
            'subtypeがその他' => [['subtype' => 'message'], false],
        ];
    }
}
