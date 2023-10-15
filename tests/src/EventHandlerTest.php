<?php declare(strict_types=1);

namespace Tests\Kirameki\Event;

use Kirameki\Core\Exceptions\InvalidTypeException;
use Kirameki\Core\Testing\TestCase;
use Kirameki\Event\Event;
use Kirameki\Event\EventHandler;
use stdClass;
use Tests\Kirameki\Event\Samples\EventA;
use Tests\Kirameki\Event\Samples\EventB;

final class EventHandlerTest extends TestCase
{
    public function test_instantiate(): void
    {
        $handler = new EventHandler();
        $this->assertSame(Event::class, $handler->class);
        $this->assertFalse($handler->hasListeners());
    }

    public function test_instantiate_with_class(): void
    {
        $class = new class extends Event {};
        $handler = new EventHandler($class::class);

        $this->assertSame($class::class, $handler->class);
        $this->assertFalse($handler->hasListeners());
    }

    public function test_instantiate_wrong_class(): void
    {
        $this->expectExceptionMessage('Expected class to be instance of Kirameki\Event\Event, got stdClass');
        $this->expectException(InvalidTypeException::class);
        new EventHandler(stdClass::class);
    }

    public function test_append(): void
    {
        $handler = new EventHandler(EventA::class);

        $called = false;
        $handler->append(function() use (&$called) { $called = true; });
        $this->assertFalse($called);
        $this->assertTrue($handler->hasListeners());

        $handler->emit(new EventA());

        $this->assertTrue($called);
        $this->assertTrue($handler->hasListeners());
    }

    public function test_append_once(): void
    {
        $handler = new EventHandler(EventA::class);

        $called = false;
        $handler->append(function() use (&$called) { $called = true; }, true);
        $this->assertFalse($called);
        $this->assertTrue($handler->hasListeners());

        $handler->emit(new EventA());

        $this->assertTrue($called);
        $this->assertFalse($handler->hasListeners());
    }

    public function test_prepend(): void
    {
        $handler = new EventHandler(EventA::class);

        $list = [];
        $handler->append(function() use (&$list) { $list[] = 'a'; });
        $handler->prepend(function() use (&$list) { $list[] = 'b'; });
        $this->assertSame([], $list);
        $this->assertTrue($handler->hasListeners());

        $handler->emit(new EventA());

        $this->assertSame(['b', 'a'], $list);
        $this->assertTrue($handler->hasListeners());
    }

    public function test_prepend_once(): void
    {
        $handler = new EventHandler(EventA::class);

        $called = false;
        $handler->prepend(function() use (&$called) { $called = true; }, true);
        $this->assertFalse($called);
        $this->assertTrue($handler->hasListeners());

        $handler->emit(new EventA());

        $this->assertTrue($called);
        $this->assertFalse($handler->hasListeners());
    }

    public function test_emit(): void
    {
        $event = new class extends Event {};
        $handler = new EventHandler($event::class);
        $emitted = 0;
        $callback = function($e) use ($event, &$emitted) {
            $emitted++;
            $this->assertSame($event, $e);
        };

        $handler->append($callback);
        $handler->append($callback);
        $count = $handler->emit($event);

        $this->assertSame(2, $emitted);
        $this->assertSame(2, $count);
        $this->assertTrue($handler->hasListeners());
    }

    public function test_emit_child_class(): void
    {
        $event = new class extends Event {};
        $handler = new EventHandler(Event::class);

        $emitted = 0;
        $handler->append(function($e) use ($event, &$emitted) {
            $emitted++;
            $this->assertSame($event, $e);
        });
        $count = $handler->emit($event);

        $this->assertSame(1, $emitted);
        $this->assertSame(1, $count);
        $this->assertTrue($handler->hasListeners());
    }

    public function test_emit_and_evict(): void
    {
        $event = new class extends Event {};
        $handler = new EventHandler(Event::class);

        $emitted = 0;
        $handler->append(function(Event $e) use (&$emitted) {
            $e->evictCallback();
            $emitted++;
        });

        $this->assertTrue($handler->hasListeners());
        $this->assertSame(1, $handler->emit($event));
        $this->assertSame(0, $handler->emit($event));
        $this->assertFalse($handler->hasListeners());
        $this->assertSame(1, $emitted);
    }

    public function test_emit_and_cancel(): void
    {
        $event = new class extends Event {};
        $handler = new EventHandler(Event::class);

        $emitted = 0;
        $handler->append(function(Event $e) use (&$emitted) {
            $e->cancel();
            $this->assertTrue($e->isCanceled());
            $emitted++;
        });
        $handler->append(function(Event $e) use (&$emitted) {
            $emitted++;
        });

        $this->assertSame(1, $handler->emit($event, $canceled));
        $this->assertFalse($event->isCanceled());
        $this->assertSame(1, $emitted);
        $this->assertTrue($canceled);
        $this->assertSame(1, $handler->emit($event));
        $this->assertSame(2, $emitted);
        $this->assertTrue($handler->hasListeners());
    }

    public function test_emit_invalid_class(): void
    {
        $this->expectExceptionMessage('Expected event to be instance of ' . EventA::class . ', got ' . EventB::class);
        $this->expectException(InvalidTypeException::class);
        $event1 = new EventA();
        $event2 = new EventB();
        $handler = new EventHandler($event1::class);
        $handler->emit($event2);
    }

    public function test_removeListener(): void
    {
        $handler = new EventHandler(Event::class);
        $callback1 = fn() => 1;
        $callback2 = fn() => 1;

        $handler->append($callback1);
        $handler->append($callback2);
        $handler->append($callback1);

        $this->assertTrue($handler->hasListeners());
        $this->assertSame(2, $handler->removeListener($callback1));
        $this->assertSame(1, $handler->removeListener($callback2));
        $this->assertFalse($handler->hasListeners());
    }

    public function test_removeAllListeners(): void
    {
        $handler = new EventHandler(Event::class);
        $handler->append(fn() => 1);
        $handler->append(fn() => 1);

        $this->assertTrue($handler->hasListeners());
        $this->assertSame(2, $handler->removeAllListeners());
        $this->assertFalse($handler->hasListeners());
    }

    public function test_hasListener(): void
    {
        $handler = new EventHandler();
        $this->assertSame(Event::class, $handler->class);
        $this->assertFalse($handler->hasListeners());
        $handler->append(fn() => 1);
        $this->assertTrue($handler->hasListeners());
    }

    public function test_hasNoListener(): void
    {
        $handler = new EventHandler();
        $this->assertSame(Event::class, $handler->class);
        $this->assertTrue($handler->hasNoListeners());

        $handler->append(fn() => 1);
        $this->assertFalse($handler->hasNoListeners());
    }
}
