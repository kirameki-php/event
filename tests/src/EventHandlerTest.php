<?php declare(strict_types=1);

namespace Tests\Kirameki\Event;

use Kirameki\Event\Event;
use Kirameki\Event\EventHandler;
use Kirameki\Event\Listener;
use Tests\Kirameki\Event\Samples\Saving;

class EventHandlerTest extends TestCase
{
    protected EventHandler $handler;

    /**
     * @var array<int, Event>
     */
    protected array $results = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new EventHandler();
    }

    public function test_listen_from_Closure(): void
    {
        $this->handler->listen(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event1 = new Saving('test');
        $event2 = new Saving('test');
        $this->handler->dispatch($event1);
        $this->handler->dispatch($event2);

        self::assertCount(2, $this->results);
        self::assertSame($event1, $this->results[0]);
        self::assertSame($event2, $this->results[1]);
    }

    public function test_listen_from_Listener(): void
    {
        $listener = new Listener(fn(Saving $e) => $this->results[] = $e);
        $this->handler->listen(Saving::class, $listener);

        $event1 = new Saving('test');
        $this->handler->dispatch($event1);

        self::assertCount(1, $this->results);
        self::assertSame($event1, $this->results[0]);
    }

    public function testListen_with_propagation_stopped(): void
    {
        $this->handler->listen(Saving::class, fn(Saving $e) => $e->stopPropagation());
        $this->handler->listen(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event = new Saving('test');
        $this->handler->dispatch($event);

        self::assertCount(0, $this->results);
    }

    public function test_listenOnce(): void
    {
        $this->handler->listenOnce(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event = new Saving('test');
        $this->handler->dispatch($event);
        $this->handler->dispatch($event);

        self::assertCount(1, $this->results);
        self::assertSame($event, $this->results[0]);
    }

    public function test_listenOnce_with_propagation_stopped(): void
    {
        $this->handler->listenOnce(Saving::class, fn(Saving $e) => $e->stopPropagation());
        $this->handler->listen(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event1 = new Saving('test');
        $event2 = new Saving('test');
        $this->handler->dispatch($event1);
        $this->handler->dispatch($event2);

        self::assertCount(1, $this->results);
        self::assertSame($event2, $this->results[0]);
    }

    public function test_hasListeners(): void
    {
        self::assertFalse($this->handler->hasListeners(Saving::class));

        $this->handler->listen(Saving::class, fn(Saving $e) => true);

        self::assertTrue($this->handler->hasListeners(Saving::class));
    }

    public function test_dispatch(): void
    {
        $this->handler->listen(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event1 = new Saving('test');
        $this->handler->dispatch($event1);

        self::assertCount(1, $this->results);
        self::assertSame($event1, $this->results[0]);
    }

    public function test_dispatchClass(): void
    {
        $this->handler->listen(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $this->handler->dispatchClass(Saving::class, 'foo');

        self::assertCount(1, $this->results);
        self::assertInstanceOf(Saving::class, $this->results[0]);
    }

    public function test_removeListener(): void
    {
        $callback = fn(Saving $e) => $this->results[] = $e;

        self::assertFalse($this->handler->removeListener(Saving::class, $callback));
        self::assertFalse($this->handler->hasListeners(Saving::class));

        $this->handler->listen(Saving::class, $callback);

        self::assertTrue($this->handler->hasListeners(Saving::class));
        self::assertTrue($this->handler->removeListener(Saving::class, $callback));
        self::assertFalse($this->handler->hasListeners(Saving::class));
    }

    public function test_removeListener_using_Listener(): void
    {
        $listener = new Listener(fn(Saving $e) => $this->results[] = $e);

        self::assertFalse($this->handler->removeListener(Saving::class, $listener));

        $this->handler->listen(Saving::class, $listener);

        self::assertTrue($this->handler->removeListener(Saving::class, $listener));
        self::assertFalse($this->handler->hasListeners(Saving::class));
    }

    public function test_removeAllListeners(): void
    {
        self::assertFalse($this->handler->removeAllListeners(Saving::class));

        $this->handler->listen(Saving::class, fn(Saving $e) => true);
        $this->handler->listen(Saving::class, fn(Saving $e) => false);

        self::assertTrue($this->handler->hasListeners(Saving::class));
        self::assertTrue($this->handler->removeAllListeners(Saving::class));
        self::assertFalse($this->handler->hasListeners(Saving::class));
    }

    public function test_onListenerAdded(): void
    {
        $this->handler->onListenerAdded(function (string $name) {
            self::assertSame(Saving::class, $name);
        });

        $this->handler->listen(Saving::class, fn(Saving $e) => true);
    }

    public function test_onListenerRemoved_from_removeListener(): void
    {
        $this->handler->onListenerRemoved(function (string $name) {
            self::assertSame(Saving::class, $name);
        });

        $callback = fn(Saving $e) => true;
        $this->handler->listen(Saving::class, $callback);
        $this->handler->removeListener(Saving::class, $callback);
    }

    public function test_onListenerRemoved_from_removeAllListeners(): void
    {
        $this->handler->onListenerRemoved(function (string $name) {
            self::assertSame(Saving::class, $name);
        });

        $this->handler->listen(Saving::class, fn(Saving $e) => true);
        $this->handler->removeAllListeners(Saving::class);
    }

    public function test_onDispatched(): void
    {
        $event1 = new Saving('test');

        $this->handler->onDispatched(function (Event $e) use ($event1) {
            self::assertSame($event1, $e);
        });

        $this->handler->dispatch($event1);
    }
}
