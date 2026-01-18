<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RelayCli;
use Illuminate\Process\PendingProcess;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Mockery;
use Tests\TestCase;

class RelayCliTest extends TestCase
{
    /**
     * @test
     * @doesNotPerformAssertions
     * @return void
     */
    public function handle_正常にコマンドが実行されること(): void
    {
        config()->set('services.slack.cli_process_path', '/test/path');
        config()->set('services.slack.cli_command', 'echo');
        config()->set('services.slack.cli_command_message_option', '-n');
        config()->set('services.slack.cli_command_other_options', null);

        Log::swap($logSpy = Mockery::spy(Log::getFacadeRoot()));

        $processResult = Mockery::mock(ProcessResult::class);
        $processResult->shouldReceive('successful')->andReturn(true);
        $processResult->shouldReceive('output')->andReturn('test output');

        $pendingProcess = Mockery::mock(PendingProcess::class);
        $pendingProcess->shouldReceive('forever')->andReturnSelf();
        $pendingProcess->shouldReceive('run')
            ->once()
            ->with(['echo', '-n', 'From Slack. Payload: test'])
            ->andReturn($processResult);

        Process::shouldReceive('path')
            ->once()
            ->with('/test/path')
            ->andReturn($pendingProcess);

        $job = new RelayCli('test');
        $job->handle();

        $logSpy->shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message, $context) {
                if ($message !== 'RelayCli Execution Success') {
                    return false;
                }
                if (!isset($context['command'], $context['output'])) {
                    return false;
                }
                if ($context['command'] !== 'echo -n From Slack. Payload: test') {
                    return false;
                }
                return $context['output'] === 'test output';
            });
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @return void
     */
    public function handle_特殊な文字列でも安全に処理されること(): void
    {
        config()->set('services.slack.cli_process_path', '/test/path');
        config()->set('services.slack.cli_command', 'echo');
        config()->set('services.slack.cli_command_message_option', '-n');
        config()->set('services.slack.cli_command_other_options', null);

        Log::swap($logSpy = Mockery::spy(Log::getFacadeRoot()));

        // 危険文字を多く含むペイロード（クォート崩れ・コマンド注入の検証）
        $payload = 'name="Alice"; rm -rf / && echo hi `whoami` $(date) \'single\' "double" $HOME \\';
        $payload .= "\nline2";

        $expectedMessage = 'From Slack. Payload: ' . $payload;

        $processResult = Mockery::mock(ProcessResult::class);
        $processResult->shouldReceive('successful')->andReturn(true);
        $processResult->shouldReceive('output')->andReturn('safe output');

        $pendingProcess = Mockery::mock(PendingProcess::class);
        $pendingProcess->shouldReceive('forever')->andReturnSelf();
        $pendingProcess->shouldReceive('run')
            ->once()
            ->with(['echo', '-n', $expectedMessage])
            ->andReturn($processResult);

        Process::shouldReceive('path')
            ->once()
            ->with('/test/path')
            ->andReturn($pendingProcess);

        $job = new RelayCli($payload);
        $job->handle();

        $logSpy->shouldHaveReceived('info')
            ->once()
            ->with('RelayCli Execution Success', Mockery::type('array'));
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @return void
     */
    public function handle_cli_process_pathが無効な場合エラーログが出力されること(): void
    {
        config()->set('services.slack.cli_process_path', '');
        config()->set('services.slack.cli_command', 'echo');
        config()->set('services.slack.cli_command_message_option', '-n');

        Log::swap($logSpy = Mockery::spy(Log::getFacadeRoot()));

        $job = new RelayCli('test');
        $job->handle();

        $logSpy->shouldHaveReceived('error')
            ->once()
            ->with('Invalid slack cli process path config.');

        $logSpy->shouldNotHaveReceived('info');
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @return void
     */
    public function handle_cli_commandが無効な場合エラーログが出力されること(): void
    {
        config()->set('services.slack.cli_process_path', '/test/path');
        config()->set('services.slack.cli_command', '');
        config()->set('services.slack.cli_command_message_option', '-n');

        Log::swap($logSpy = Mockery::spy(Log::getFacadeRoot()));

        $job = new RelayCli('test');
        $job->handle();

        $logSpy->shouldHaveReceived('error')
            ->once()
            ->with('Invalid slack cli command config.');

        $logSpy->shouldNotHaveReceived('info');
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @return void
     */
    public function handle_cli_command_message_optionが無効な場合エラーログが出力されること(): void
    {
        config()->set('services.slack.cli_process_path', '/test/path');
        config()->set('services.slack.cli_command', 'echo');
        config()->set('services.slack.cli_command_message_option', '');

        Log::swap($logSpy = Mockery::spy(Log::getFacadeRoot()));

        $job = new RelayCli('test');
        $job->handle();

        $logSpy->shouldHaveReceived('error')
            ->once()
            ->with('Invalid slack cli command message option config.');

        $logSpy->shouldNotHaveReceived('info');
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @return void
     */
    public function handle_プロセス実行に失敗した場合エラーログが出力されること(): void
    {
        config()->set('services.slack.cli_process_path', '/test/path');
        config()->set('services.slack.cli_command', 'echo');
        config()->set('services.slack.cli_command_message_option', '-n');
        config()->set('services.slack.cli_command_other_options', null);

        Log::swap($logSpy = Mockery::spy(Log::getFacadeRoot()));

        $processResult = Mockery::mock(ProcessResult::class);
        $processResult->shouldReceive('successful')->andReturn(false);
        $processResult->shouldReceive('exitCode')->andReturn(1);
        $processResult->shouldReceive('errorOutput')->andReturn('command failed');

        $pendingProcess = Mockery::mock(PendingProcess::class);
        $pendingProcess->shouldReceive('forever')->andReturnSelf();
        $pendingProcess->shouldReceive('run')->andReturn($processResult);

        Process::shouldReceive('path')->andReturn($pendingProcess);

        $job = new RelayCli('test');
        $job->handle();

        $logSpy->shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($message, $context) {
                if ($message !== 'RelayCli Execution Failed') {
                    return false;
                }
                if (!isset($context['command'], $context['exit_code'], $context['error'])) {
                    return false;
                }
                if ($context['exit_code'] !== 1) {
                    return false;
                }
                return $context['error'] === 'command failed';
            });

        $logSpy->shouldNotHaveReceived('info');
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @return void
     */
    public function handle_cli_command_other_optionsが設定されている場合追加オプションが含まれること(): void
    {
        config()->set('services.slack.cli_process_path', '/test/path');
        config()->set('services.slack.cli_command', 'slack');
        config()->set('services.slack.cli_command_message_option', '-m');
        config()->set('services.slack.cli_command_other_options', '--channel general --icon :ghost:');

        Log::swap($logSpy = Mockery::spy(Log::getFacadeRoot()));

        $processResult = Mockery::mock(ProcessResult::class);
        $processResult->shouldReceive('successful')->andReturn(true);
        $processResult->shouldReceive('output')->andReturn('sent');

        $pendingProcess = Mockery::mock(PendingProcess::class);
        $pendingProcess->shouldReceive('forever')->andReturnSelf();
        $pendingProcess->shouldReceive('run')
            ->once()
            ->with(['slack', '-m', 'From Slack. Payload: hello', '--channel', 'general', '--icon', ':ghost:'])
            ->andReturn($processResult);

        Process::shouldReceive('path')
            ->once()
            ->with('/test/path')
            ->andReturn($pendingProcess);

        $job = new RelayCli('hello');
        $job->handle();

        $logSpy->shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'RelayCli Execution Success'
                    && str_contains($context['command'], '--channel general --icon :ghost:');
            });
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @return void
     */
    public function failed_例外情報がログに記録されること(): void
    {
        Log::swap($logSpy = Mockery::spy(Log::getFacadeRoot()));

        $exception = new \Exception('Test exception message');

        $job = new RelayCli('test payload');
        $job->failed($exception);

        $logSpy->shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($message, $context) {
                if ($message !== 'Job Failed: RelayCli') {
                    return false;
                }
                if (!isset($context['payload'], $context['exception'], $context['trace'])) {
                    return false;
                }
                if ($context['payload'] !== 'test payload') {
                    return false;
                }
                return $context['exception'] === 'Test exception message';
            });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
