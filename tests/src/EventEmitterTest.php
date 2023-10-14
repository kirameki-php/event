<?php declare(strict_types=1);

namespace Tests\Kirameki\Event;

use Kirameki\Core\Event;
use Kirameki\Event\EventEmitter;
use Tests\Kirameki\Event\Samples\Saving;

class EventEmitterTest extends TestCase
{
    protected EventEmitter $handler;

    /**
     * @var array<int, Event>
     */
    protected array $results = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new EventEmitter();
    }

    public function test_append_from_Closure(): void
    {
        $this->handler->append(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event1 = new Saving('test');
        $event2 = new Saving('test');
        $this->handler->emit($event1);
        $this->handler->emit($event2);

        self::assertCount(2, $this->results);
        self::assertSame($event1, $this->results[0]);
        self::assertSame($event2, $this->results[1]);
    }

    public function test_append_with_cancel(): void
    {
        $this->handler->append(Saving::class, fn(Saving $e) => $e->cancel());
        $this->handler->append(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event = new Saving('test');
        $this->handler->emit($event);

        self::assertCount(0, $this->results);
    }

    public function test_hasListeners(): void
    {
        self::assertFalse($this->handler->hasListeners(Saving::class));

        $this->handler->append(Saving::class, fn(Saving $e) => true);

        self::assertTrue($this->handler->hasListeners(Saving::class));
    }

    public function test_dispatch(): void
    {
        $this->handler->append(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $event1 = new Saving('test');
        $this->handler->emit($event1);

        self::assertCount(1, $this->results);
        self::assertSame($event1, $this->results[0]);
    }

    public function test_dispatchIfListening(): void
    {
        $this->handler->append(Saving::class, fn(Saving $e) => $this->results[] = $e);

        $this->handler->emitIfListening(Saving::class, fn() => new Saving('foo'));

        self::assertCount(1, $this->results);
        self::assertInstanceOf(Saving::class, $this->results[0]);
    }

    public function test_removeListener(): void
    {
        $callback = fn(Saving $e) => $this->results[] = $e;

        self::assertFalse($this->handler->removeListener(Saving::class, $callback));
        self::assertFalse($this->handler->hasListeners(Saving::class));

        $this->handler->append(Saving::class, $callback);

        self::assertTrue($this->handler->hasListeners(Saving::class));
        self::assertSame(1, $this->handler->removeListener(Saving::class, $callback));
        self::assertFalse($this->handler->hasListeners(Saving::class));
    }

    public function test_removeAllListeners(): void
    {
        self::assertFalse($this->handler->removeAllListeners(Saving::class));

        $this->handler->append(Saving::class, fn(Saving $e) => true);
        $this->handler->append(Saving::class, fn(Saving $e) => false);

        self::assertTrue($this->handler->hasListeners(Saving::class));
        self::assertTrue($this->handler->removeAllListeners(Saving::class));
        self::assertFalse($this->handler->hasListeners(Saving::class));
    }

    public function test_onEmitted(): void
    {
        $event1 = new Saving('test');

        $this->handler->onEmitted(function (Event $e) use ($event1) {
            self::assertSame($event1, $e);
        });

        $this->handler->emit($event1);
    }
}
