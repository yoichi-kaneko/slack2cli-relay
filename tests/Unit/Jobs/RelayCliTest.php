<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RelayCli;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class RelayCliTest extends TestCase
{
    /**
     * @test
     * @doesNotPerformAssertions
     * @return void
     */
    public function handle_特殊な文字列でも無害化に成功していること(): void
    {
        // 無害なコマンドに差し替え（printf は副作用なしで標準出力に文字列を出す）
        config()->set('services.slack.cli_command_format', 'printf %s');

        // Log Facade をスパイ（モック）する
        Log::swap($spy = Mockery::spy(Log::getFacadeRoot()));

        // 危険文字を多く含むペイロード（クォート崩れ・コマンド注入の検証）
        $payload = 'name="Alice"; rm -rf / && echo hi `whoami` $(date) \'single\' "double" $HOME \\';
        $payload .= "\nline2";

        $job = new RelayCli($payload);
        $job->handle();

        // info ログが想定の形式で呼ばれていることを確認
        $expectedOutput = 'From Slack. Payload: ' . $payload;

        $spy->shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message, $context) use ($expectedOutput) {
                if ($message !== 'RelayCli executed.') {
                    return false;
                }
                if (!isset($context['command'], $context['exit_code'], $context['output'])) {
                    return false;
                }
                // 無害なコマンドが使われていること（printf で始まる）
                if (strpos($context['command'], 'printf ') !== 0) {
                    return false;
                }
                // 正常終了
                if ($context['exit_code'] !== 0) {
                    return false;
                }
                // 出力が期待通り（生のペイロード）
                return $context['output'] === $expectedOutput;
            });
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @return void
     */
    public function handle_処理に失敗したときエラーログが保存されること(): void
    {
        // %s が含まれない無効なフォーマット
        config()->set('services.slack.cli_command_format', 'printf');

        Log::swap($spy = Mockery::spy(Log::getFacadeRoot()));

        $job = new RelayCli('any');
        $job->handle();

        // エラーログが出る
        $spy->shouldHaveReceived('error')
            ->once()
            ->with('Invalid slack cli command format config.');

        // 成功ログは呼ばれていない
        $spy->shouldNotHaveReceived('info');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
