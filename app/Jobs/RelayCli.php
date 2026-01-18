<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RelayCli implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $payload,
    )
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $messageRaw = 'From Slack. Payload: ' . $this->payload;
        $message = escapeshellarg($messageRaw);

        $cliCommand = config('services.slack.cli_command');
        $messageOption = config('services.slack.cli_command_message_option');

        if (!is_string($cliCommand) || empty($cliCommand)) {
            Log::error('Invalid slack cli command config.');
            return;
        }

        if (!is_string($messageOption) || empty($messageOption)) {
            Log::error('Invalid slack cli command message option config.');
            return;
        }

        $command = sprintf('%s %s %s', $cliCommand, $messageOption, $message);

        $output = [];
        $exitCode = null;
        exec($command, $output, $exitCode);

        Log::info('RelayCli executed.', [
            'command' => $command,
            'exit_code' => $exitCode,
            'output' => implode("\n", $output),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Job Failed: RelayCli', [
            'payload' => $this->payload,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
