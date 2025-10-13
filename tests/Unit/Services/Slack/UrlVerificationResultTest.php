<?php

namespace Tests\Unit\Services\Slack;

use App\Services\Slack\UrlVerificationResult;
use Tests\TestCase;

class UrlVerificationResultTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideResults
     * @param UrlVerificationResult $result
     * @param array{ok:bool,bad_request:bool,not_applicable:bool,challenge:?string} $expected
     * @return void
     */
    public function isOk_isBadRequest_isNotApplicable_全条件分岐を網羅して期待通り判定できること(UrlVerificationResult $result, array $expected): void
    {
        $this->assertSame($expected['ok'], $result->isOk());
        $this->assertSame($expected['bad_request'], $result->isBadRequest());
        $this->assertSame($expected['not_applicable'], $result->isNotApplicable());
        $this->assertSame($expected['challenge'], $result->challenge);
    }

    /**
     * @return array<string, array{0: UrlVerificationResult, 1: array{ok:bool,bad_request:bool,not_applicable:bool,challenge:?string}}>
     */
    public static function provideResults(): array
    {
        return [
            'ok' => [
                UrlVerificationResult::ok('challenge-123'),
                ['ok' => true, 'bad_request' => false, 'not_applicable' => false, 'challenge' => 'challenge-123'],
            ],
            'bad_request' => [
                UrlVerificationResult::badRequest(),
                ['ok' => false, 'bad_request' => true, 'not_applicable' => false, 'challenge' => null],
            ],
            'not_applicable' => [
                UrlVerificationResult::notApplicable(),
                ['ok' => false, 'bad_request' => false, 'not_applicable' => true, 'challenge' => null],
            ],
        ];
    }
}
