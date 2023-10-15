<?php declare(strict_types=1);

namespace Tests\Kirameki\Event;

use DateTime;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Event\Event;
use Kirameki\Event\EventManager;
use Tests\Kirameki\Event\Samples\Saving;

class EventEmitterTest extends TestCase
{
    protected EventManager $emitter;

    /**
     * @var array<int, Event>
     */
    protected array $results = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->emitter = new EventManager();
    }

    public function test_on_valid(): void
    {
        $this->emitter->on(fn(Saving $e) => $this->results[] = $e);

        $event1 = new Saving('test');
        $event2 = new Saving('test');
        $this->emitter->emit($event1);
        $this->emitter->emit($event2);

        $this->assertSame([$event1, $event2], $this->results);
    }

    public function test_on_with_empty_arg(): void
    {
        $this->expectExceptionMessage('The first parameter of the callback must be an instance of Event.');
        $this->expectException(LogicException::class);

        $this->emitter->on(fn() => true);
    }

    public function test_on_with_invalid_arg(): void
    {
        $this->expectExceptionMessage('The first parameter of the callback must be an instance of Event.');
        $this->expectException(LogicException::class);

        $this->emitter->on(fn(DateTime $t) => true);
    }

    public function test_once_valid(): void
    {
        $this->emitter->once(fn(Saving $e) => $this->results[] = $e);

        $event1 = new Saving('test');
        $event2 = new Saving('test');
        $this->emitter->emit($event1);
        $this->emitter->emit($event2);

        $this->assertSame([$event1], $this->results);
    }

    public function test_once_with_empty_arg(): void
    {
        $this->expectExceptionMessage('The first parameter of the callback must be an instance of Event.');
        $this->expectException(LogicException::class);

        $this->emitter->once(fn() => true);
    }

    public function test_once_with_invalid_arg(): void
    {
        $this->expectExceptionMessage('The first parameter of the callback must be an instance of Event.');
        $this->expectException(LogicException::class);

        $this->emitter->once(fn(DateTime $t) => true);
    }

    public function test_append_valid(): void
    {
        $this->emitter->append(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event1 = new Saving('test');
        $event2 = new Saving('test');
        $this->emitter->emit($event1);
        $this->emitter->emit($event2);

        $this->assertSame([$event1, $event2], $this->results);
    }

    public function test_append_once(): void
    {
        $this->emitter->append(Saving::class, fn(Saving $e) => $this->results[] = $e, true);

        $this->assertTrue($this->emitter->hasListeners(Saving::class));

        $event1 = new Saving('test');
        $this->emitter->emit($event1);

        $this->assertFalse($this->emitter->hasListeners(Saving::class));

        $event2 = new Saving('test');
        $this->emitter->emit($event2);

        $this->assertFalse($this->emitter->hasListeners(Saving::class));
        $this->assertSame([$event1], $this->results);
    }

    public function test_append_with_cancel(): void
    {
        $this->emitter->append(Saving::class, fn(Saving $e) => $e->cancel());
        $this->emitter->append(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event = new Saving('test');
        $this->emitter->emit($event);

        $this->assertTrue($this->emitter->hasListeners(Saving::class));
        $this->assertCount(0, $this->results);
    }

    public function test_prepend_valid(): void
    {
        $called = [];
        $this->emitter->append(Saving::class, function () use (&$called) { $called[] = 'a'; });
        $this->emitter->prepend(Saving::class, function () use (&$called) { $called[] = 'b'; });

        $this->emitter->emit(new Saving('test'));

        $this->assertSame(['b', 'a'], $called);
    }

    public function test_prepend_once(): void
    {
        $this->emitter->prepend(Saving::class, fn(Saving $e) => $this->results[] = $e, true);

        $this->assertTrue($this->emitter->hasListeners(Saving::class));

        $event1 = new Saving('test');
        $this->emitter->emit($event1);

        $this->assertFalse($this->emitter->hasListeners(Saving::class));

        $event2 = new Saving('test');
        $this->emitter->emit($event2);

        $this->assertFalse($this->emitter->hasListeners(Saving::class));
        $this->assertSame([$event1], $this->results);
    }

    public function test_prepend_with_cancel(): void
    {
        $this->emitter->prepend(Saving::class, fn(Saving $e) => $this->results[] = $e);
        $this->emitter->prepend(Saving::class, fn(Saving $e) => $e->cancel());

        $event = new Saving('test');
        $this->emitter->emit($event);

        $this->assertTrue($this->emitter->hasListeners(Saving::class));
        $this->assertCount(0, $this->results);
    }

    public function test_hasListeners(): void
    {
        $this->assertFalse($this->emitter->hasListeners(Saving::class));

        $this->emitter->append(Saving::class, fn(Saving $e) => true);

        $this->assertTrue($this->emitter->hasListeners(Saving::class));
    }

    public function test_emit(): void
    {
        $this->emitter->append(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event1 = new Saving('test');
        $this->emitter->emit($event1);

        $this->assertSame([$event1], $this->results);
    }

    public function test_emitIfListening_with_listener(): void
    {
        $this->emitter->append(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $this->emitter->emitIfListening(Saving::class, fn() => new Saving('foo'));

        $this->assertCount(1, $this->results);
        $this->assertInstanceOf(Saving::class, $this->results[0]);
    }

    public function test_emitIfListening_without_listener(): void
    {
        $this->emitter->emitIfListening(
            Saving::class,
            fn() => $this->results[] = new Saving('foo'),
        );

        $this->assertCount(0, $this->results);
    }

    public function test_emitIfListening_bad_type(): void
    {
        $this->expectExceptionMessage('$event must be an instance of ' . Saving::class);
        $this->expectException(LogicException::class);

        $this->emitter->append(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $this->emitter->emitIfListening(Saving::class, fn() => new DateTime());
    }

    public function test_removeListener(): void
    {
        $callback = fn(Saving $e) => $this->results[] = $e;

        $this->assertSame(0, $this->emitter->removeListener(Saving::class, $callback));
        $this->assertFalse($this->emitter->hasListeners(Saving::class));

        $this->emitter->append(Saving::class, $callback);

        $this->assertTrue($this->emitter->hasListeners(Saving::class));
        $this->assertSame(1, $this->emitter->removeListener(Saving::class, $callback));
        $this->assertFalse($this->emitter->hasListeners(Saving::class));
    }

    public function test_removeAllListeners(): void
    {
        self::assertFalse($this->emitter->removeAllListeners(Saving::class));

        $this->emitter->append(Saving::class, fn(Saving $e) => true);
        $this->emitter->append(Saving::class, fn(Saving $e) => false);

        $this->assertTrue($this->emitter->hasListeners(Saving::class));
        $this->assertTrue($this->emitter->removeAllListeners(Saving::class));
        $this->assertFalse($this->emitter->hasListeners(Saving::class));
    }

    public function test_onEmitted(): void
    {
        $event1 = new Saving('test');

        $this->emitter->onEmitted(function (Event $e) use ($event1) {
            $this->assertSame($event1, $e);
        });

        $this->emitter->emit($event1);
    }
}
