<?php

declare(strict_types=1);

namespace App\Infrastructure\Jobs;

use App\Contracts\Jobs\RelayDispatcher;
use App\Jobs\RelayCli;

final class LaravelRelayDispatcher implements RelayDispatcher
{
    public function dispatch(string $rawJson): void
    {
        RelayCli::dispatch($rawJson);
    }
}
