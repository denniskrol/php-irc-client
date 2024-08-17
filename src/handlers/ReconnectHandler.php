<?php
declare(strict_types=1);

namespace Thirdplace\Irc;

/**
 * Reconnects to the server in case of ERROR message
 */
final class ReconnectHandler implements Handler
{
    private int $n = 2;

    public function __invoke(Client $client, Message $message): void
    {
        if ($message->isError()) {
            $this->n++;
            // naive backoff sleep
            sleep($this->n);
            $client->restart();
        }
    }
}
