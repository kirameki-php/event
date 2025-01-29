<?php declare(strict_types=1);

namespace Tests\Kirameki\Event\Listeners;

use Kirameki\Core\Testing\TestCase;
use Kirameki\Event\Listeners\CallbackListener;
use stdClass;
use Tests\Kirameki\Event\Samples\EventA;

final class CallbackListenerTest extends TestCase
{
    public function test_constructor(): void
    {
        $handler = new CallbackListener(EventA::class, fn() => true);
        $this->assertSame(EventA::class, $handler->eventClass);
    }

    public function test_invoke(): void
    {
        $o = new stdClass();
        $o->value = 0;
        $event = new EventA();
        $handler = new CallbackListener($event::class, fn() => $o->value += 1);
        $handler($event);
        $this->assertFalse($event->willEvictCallback());
        $this->assertSame(1, $o->value);
        $handler($event);
        $this->assertSame(2, $o->value);
    }
}
