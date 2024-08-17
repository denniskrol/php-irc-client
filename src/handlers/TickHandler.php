<?php
declare(strict_types=1);

namespace Thirdplace\Irc;

/**
 * Invoke function periodically
 */
final class TickHandler
{
    private int $seconds;
    private $fn;
    private int $lastRun = 0;

    public function __construct(int $seconds, callable $fn)
    {
        $this->seconds = $seconds;
        $this->fn = $fn;
    }

    public function __invoke(Client $client): void
    {
        if (time() > $this->lastRun + $this->seconds) {
            $this->lastRun = time();
            ($this->fn)($client);
        }
    }
}
