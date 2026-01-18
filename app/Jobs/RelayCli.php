<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
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

        $cliProcessPath = config('services.slack.cli_process_path');
        $cliCommand = config('services.slack.cli_command');
        $messageOption = config('services.slack.cli_command_message_option');
        $otherOptions = config('services.slack.cli_command_other_options');

        if (!is_string($cliProcessPath) || empty($cliProcessPath)) {
            Log::error('Invalid slack cli process path config.');
            return;
        }

        if (!is_string($cliCommand) || empty($cliCommand)) {
            Log::error('Invalid slack cli command config.');
            return;
        }

        if (!is_string($messageOption) || empty($messageOption)) {
            Log::error('Invalid slack cli command message option config.');
            return;
        }

        // Build command array
        $command = [$cliCommand, $messageOption, $messageRaw];

        // Add other options if configured
        if (is_string($otherOptions) && !empty($otherOptions)) {
            $additionalOptions = preg_split('/\s+/', trim($otherOptions));
            if (is_array($additionalOptions)) {
                $command = array_merge($command, $additionalOptions);
            }
        }

        // Execute command using Process facade
        $result = Process::path($cliProcessPath)
            ->forever()
            ->run($command);

        if ($result->successful()) {
            Log::info('RelayCli Execution Success', [
                'command' => implode(' ', $command),
                'output' => $result->output(),
            ]);
        } else {
            Log::error('RelayCli Execution Failed', [
                'command' => implode(' ', $command),
                'exit_code' => $result->exitCode(),
                'error' => $result->errorOutput(),
            ]);
        }
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
