<?php declare(strict_types=1);

namespace Tests\Kirameki\Event;

use DateTime;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Event\Event;
use Kirameki\Event\EventEmitter;
use Kirameki\Event\EventManager;
use Kirameki\Event\Listeners\CallbackListener;
use Kirameki\Event\Listeners\CallbackOnceListener;
use Tests\Kirameki\Event\Samples\Saving;

class EventManagerTest extends TestCase
{
    protected EventManager $events;

    /**
     * @var array<int, Event>
     */
    protected array $results = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->events = new EventManager();
    }

    public function test_instanceof_EventEmitter(): void
    {
        $this->assertInstanceOf(EventEmitter::class, $this->events);
    }

    public function test_on_valid(): void
    {
        $this->events->on(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event1 = new Saving('test');
        $event2 = new Saving('test');
        $this->events->emit($event1);
        $this->events->emit($event2);

        $this->assertSame([$event1, $event2], $this->results);
    }

    public function test_on_with_invalid_arg(): void
    {
        $this->expectExceptionMessage('Expected class to be instance of Kirameki\Event\Event, got DateTime.');
        $this->expectException(InvalidArgumentException::class);

        $this->events->on(DateTime::class, fn() => true);
    }

    public function test_once_valid(): void
    {
        $this->events->once(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event1 = new Saving('test');
        $event2 = new Saving('test');
        $this->events->emit($event1);
        $this->events->emit($event2);

        $this->assertSame([$event1], $this->results);
    }

    public function test_once_with_invalid_arg(): void
    {
        $this->expectExceptionMessage('Expected class to be instance of Kirameki\Event\Event, got DateTime.');
        $this->expectException(InvalidArgumentException::class);

        $this->events->once(DateTime::class, fn(DateTime $t) => true);
    }

    public function test_append_valid(): void
    {
        $this->events->append(new CallbackListener(fn(Saving $e) => $this->results[] = $e));

        $event1 = new Saving('test');
        $event2 = new Saving('test');
        $this->events->emit($event1);
        $this->events->emit($event2);

        $this->assertSame([$event1, $event2], $this->results);
    }

    public function test_append_once(): void
    {
        $this->events->append(new CallbackOnceListener(fn(Saving $e) => $this->results[] = $e));

        $this->assertTrue($this->events->hasListeners(Saving::class));

        $event1 = new Saving('test');
        $this->events->emit($event1);

        $this->assertFalse($this->events->hasListeners(Saving::class));

        $event2 = new Saving('test');
        $this->events->emit($event2);

        $this->assertFalse($this->events->hasListeners(Saving::class));
        $this->assertSame([$event1], $this->results);
    }

    public function test_append_with_cancel(): void
    {
        $this->events->append(new CallbackListener(fn(Saving $e) => $e->cancel()));
        $this->events->append(new CallbackListener(fn(Saving $e) => $this->results[] = $e));

        $this->events->emit(new Saving('test'));

        $this->assertTrue($this->events->hasListeners(Saving::class));
        $this->assertCount(0, $this->results);
    }

    public function test_prepend_valid(): void
    {
        $called = [];
        $this->events->append(new CallbackListener(function (Saving $_) use (&$called) { $called[] = 'a'; }));
        $this->events->prepend(new CallbackListener(function (Saving $_) use (&$called) { $called[] = 'b'; }));

        $this->events->emit(new Saving('test'));

        $this->assertSame(['b', 'a'], $called);
    }

    public function test_prepend_once(): void
    {
        $this->events->prepend(new CallbackOnceListener(fn(Saving $e) => $this->results[] = $e));

        $this->assertTrue($this->events->hasListeners(Saving::class));

        $event1 = new Saving('test');
        $this->events->emit($event1);

        $this->assertFalse($this->events->hasListeners(Saving::class));

        $event2 = new Saving('test');
        $this->events->emit($event2);

        $this->assertFalse($this->events->hasListeners(Saving::class));
        $this->assertSame([$event1], $this->results);
    }

    public function test_prepend_with_cancel(): void
    {
        $this->events->prepend(new CallbackListener(fn(Saving $e) => $this->results[] = $e));
        $this->events->prepend(new CallbackListener(fn(Saving $e) => $e->cancel()));

        $event = new Saving('test');
        $this->events->emit($event, $canceled);

        $this->assertTrue($this->events->hasListeners(Saving::class));
        $this->assertCount(0, $this->results);
        $this->assertTrue($canceled);
    }

    public function test_hasListeners(): void
    {
        $this->assertFalse($this->events->hasListeners(Saving::class));

        $this->events->append(new CallbackListener(fn(Saving $e) => true));

        $this->assertTrue($this->events->hasListeners(Saving::class));
    }

    public function test_emit(): void
    {
        $this->events->append(new CallbackListener(fn(Saving $e) => $this->results[] = $e));

        $event1 = new Saving('test');
        $this->events->emit($event1);

        $this->assertSame([$event1], $this->results);
    }

    public function test_emit__with_canceled_ref(): void
    {
        $this->events->append(new CallbackListener(fn(Saving $e) => $this->results[] = $e));

        $event1 = new Saving('test');
        $this->events->emit($event1, $canceled);

        $this->assertSame([$event1], $this->results);
        $this->assertFalse($canceled);
    }

    public function test_emit__with_cancel_triggered(): void
    {
        $this->events->append(new CallbackListener(fn(Saving $e) => $e->cancel()));
        $this->events->append(new CallbackListener(fn(Saving $e) => $this->results[] = $e));

        $event1 = new Saving('test');
        $this->events->emit($event1, $canceled);

        $this->assertSame([], $this->results);
        $this->assertTrue($canceled);
    }

    public function test_removeListener(): void
    {
        $callback = new CallbackListener(fn(Saving $e) => $this->results[] = $e);

        $this->assertSame(0, $this->events->removeListener($callback));
        $this->assertFalse($this->events->hasListeners(Saving::class));

        $this->events->append($callback);

        $this->assertTrue($this->events->hasListeners(Saving::class));
        $this->assertSame(1, $this->events->removeListener($callback));
        $this->assertFalse($this->events->hasListeners(Saving::class));
    }

    public function test_removeAllListeners(): void
    {
        self::assertFalse($this->events->removeAllListeners(Saving::class));

        $this->events->append(new CallbackListener(fn(Saving $e) => true));
        $this->events->append(new CallbackListener(fn(Saving $e) => false));

        $this->assertTrue($this->events->hasListeners(Saving::class));
        $this->assertTrue($this->events->removeAllListeners(Saving::class));
        $this->assertFalse($this->events->hasListeners(Saving::class));
    }

    public function test_onEmitted(): void
    {
        $event1 = new Saving('test');

        $this->events->onEmitted(function (Event $e) use ($event1) {
            $this->assertSame($event1, $e);
        });

        $this->events->emit($event1);
    }
}
