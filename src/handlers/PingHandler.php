<?php
declare(strict_types=1);

namespace Thirdplace\Irc;

/**
 * Repond to ping with a pong
 */
final class PingHandler implements Handler
{
    public function __invoke(Client $client, Message $message): void
    {
        if ($message->isPing()) {
            $client->write($message->pong());
        }
    }
}
