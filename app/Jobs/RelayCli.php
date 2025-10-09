<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        $optionRaw = 'From Slack. Payload: ' . $this->payload;
        $option = escapeshellarg($optionRaw);

        $format = config('services.slack.cli_command_format');
        if (!is_string($format) || !str_contains($format, '%s')) {
            Log::error('Invalid slack cli command format config.');
            return;
        }

        $command = sprintf($format, $option);

        $output = [];
        $exitCode = null;
        exec($command, $output, $exitCode);

        Log::info('RelayCli executed.', [
            'command' => $command,
            'exit_code' => $exitCode,
            'output' => implode("\n", $output),
        ]);
    }
}
