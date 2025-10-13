<?php

namespace Tests\Unit\Infrastructure\Jobs;

use App\Infrastructure\Jobs\LaravelRelayDispatcher;
use App\Jobs\RelayCli;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class LaravelRelayDispatcherTest extends TestCase
{
    /**
     * @test
     */
    public function dispatch_引数のJSON文字列をRelayCliジョブにディスパッチすること(): void
    {
        Bus::fake();

        $dispatcher = new LaravelRelayDispatcher();
        $payload = '{"foo":"bar"}';

        $dispatcher->dispatch($payload);

        Bus::assertDispatched(RelayCli::class, function (RelayCli $job) use ($payload) {
            $ref = new \ReflectionClass($job);
            $prop = $ref->getProperty('payload');
            $prop->setAccessible(true);
            return $prop->getValue($job) === $payload;
        });

        Bus::assertDispatchedTimes(RelayCli::class, 1);
    }
}
