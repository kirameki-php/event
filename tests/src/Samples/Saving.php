<?php declare(strict_types=1);

namespace Tests\Kirameki\Event\Samples;

use Kirameki\Core\Event;

class Saving extends Event
{
    public function __construct(protected readonly string $target)
    {
    }
}
