<?php declare(strict_types=1);

namespace Kirameki\Event;

abstract class Event
{
    /**
     * @param bool $propagate
     * @param bool $evictCallback
     */
    public function __construct(
        protected bool $propagate = true,
        protected bool $evictCallback = false,
    ) {
    }

    /**
     * @return void
     */
    public function stopPropagation(): void
    {
        $this->propagate = false;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return !$this->propagate;
    }

    /**
     * Mark signal callback for removal.
     * When this is set to **true**, the signal callback will be removed.
     *
     * @return $this
     */
    public function evictCallback(bool $toggle = true): static
    {
        $this->evictCallback = $toggle;
        return $this;
    }

    /**
     * Returns whether the signal callback should be removed.
     *
     * @return bool
     */
    public function willEvictCallback(): bool
    {
        return $this->evictCallback;
    }
}
