<?php declare(strict_types=1);

namespace Tests\Kirameki\Event\Listeners;

use Kirameki\Core\Testing\TestCase;
use Kirameki\Event\Listeners\CallbackListener;
use Tests\Kirameki\Event\Samples\EventA;

final class CallbackListenerTest extends TestCase
{
    public function test_constructor_one_arg(): void
    {
        $handler = new CallbackListener(EventA::class, fn(EventA $e) => true);
        $this->assertSame(EventA::class, $handler->eventClass);
    }
}
