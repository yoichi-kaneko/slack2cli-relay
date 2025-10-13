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
     * @param array{is_challenge:bool,bad_request:bool,not_applicable:bool,challenge:?string} $expected
     * @return void
     */
    public function isChallenge_isBadRequest_isNotApplicable_全条件分岐を網羅して期待通り判定できること(UrlVerificationResult $result, array $expected): void
    {
        $this->assertSame($expected['is_challenge'], $result->isChallenge());
        $this->assertSame($expected['bad_request'], $result->isBadRequest());
        $this->assertSame($expected['not_applicable'], $result->isNotApplicable());
        $this->assertSame($expected['challenge'], $result->challenge);
    }

    /**
     * @return array<string, array{0: UrlVerificationResult, 1: array{is_challenge:bool,bad_request:bool,not_applicable:bool,challenge:?string}}>
     */
    public static function provideResults(): array
    {
        return [
            'challenge' => [
                UrlVerificationResult::challenge('challenge-123'),
                ['is_challenge' => true, 'bad_request' => false, 'not_applicable' => false, 'challenge' => 'challenge-123'],
            ],
            'bad_request' => [
                UrlVerificationResult::badRequest(),
                ['is_challenge' => false, 'bad_request' => true, 'not_applicable' => false, 'challenge' => null],
            ],
            'not_applicable' => [
                UrlVerificationResult::notApplicable(),
                ['is_challenge' => false, 'bad_request' => false, 'not_applicable' => true, 'challenge' => null],
            ],
        ];
    }
}
