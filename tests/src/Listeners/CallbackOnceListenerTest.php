<?php declare(strict_types=1);

namespace Tests\Kirameki\Event\Listeners;

use Kirameki\Core\Testing\TestCase;
use Kirameki\Event\Listeners\CallbackOnceListener;
use stdClass;
use Tests\Kirameki\Event\Samples\EventA;

final class CallbackOnceListenerTest extends TestCase
{
    public function test_constructor(): void
    {
        $handler = new CallbackOnceListener(EventA::class, fn() => true);
        $this->assertSame(EventA::class, $handler->eventClass);
    }

    public function test_invoke(): void
    {
        $o = new stdClass();
        $o->value = 0;
        $event = new EventA();
        $handler = new CallbackOnceListener($event::class, fn() => $o->value += 1);
        $handler->invoke($event);
        $this->assertSame(1, $o->value);
        $this->assertTrue($event->willEvictCallback());
    }
}
