<?php declare(strict_types=1);

namespace Tests\Kirameki\Event;

use Kirameki\Core\Func;
use Kirameki\Event\EventHandler;
use Tests\Kirameki\Event\Samples\CustomManager;
use Tests\Kirameki\Event\Samples\EventA;

class HandlesEventsTest extends TestCase
{
    public function test_getEventHandlerOrNull__no_handler(): void
    {
        $manager = new CustomManager;
        $handler = $manager->getHandlerOrNull(EventA::class);
        $this->assertNull($handler);
    }

    public function test_getEventHandlerOrNull__has_handler(): void
    {
        $manager = new CustomManager;
        $manager->resolveHandler(EventA::class);
        $handler = $manager->getHandlerOrNull(EventA::class);
        $this->assertInstanceOf(EventHandler::class, $handler);
        $this->assertSame(EventA::class, $handler->class);
    }

    public function test_resolveEventHandler(): void
    {
        $manager = new CustomManager;
        $handler = $manager->resolveHandler(EventA::class);
        $this->assertInstanceOf(EventHandler::class, $handler);
        $this->assertSame(EventA::class, $handler->class);
    }

    public function test_eventHandlerExist(): void
    {
        $manager = new CustomManager;
        $this->assertFalse($manager->hasListeners(EventA::class));
        $manager->resolveHandler(EventA::class)->do(Func::true());
        $this->assertTrue($manager->hasListeners(EventA::class));
        $manager->removeHandler(EventA::class);
        $this->assertFalse($manager->hasListeners(EventA::class));
    }

    public function test_removeEventHandler(): void
    {
        $manager = new CustomManager;
        $manager->resolveHandler(EventA::class);
        $this->assertTrue($manager->removeHandler(EventA::class));
        $this->assertFalse($manager->hasListeners(EventA::class));
        $this->assertNull($manager->getHandlerOrNull(EventA::class));
        $this->assertFalse($manager->removeHandler(EventA::class));
    }

    public function test_emitEvent(): void
    {
        $manager = new CustomManager;
        $event = new EventA;
        $emitted = $manager->emit($event);
        $this->assertSame(0, $emitted);

        $manager->resolveHandler(EventA::class)->doOnce(Func::true());
        $emitted = $manager->emit($event);
        $this->assertSame(1, $emitted);

        $manager->resolveHandler(EventA::class)->do(Func::true());
        $emitted = $manager->emit($event);
        $this->assertSame(1, $emitted);
    }
}
