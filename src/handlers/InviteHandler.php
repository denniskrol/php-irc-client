<?php
declare(strict_types=1);

namespace Thirdplace\Irc;

/**
 * Accept channel invites from owner
 */
final class InviteHandler implements Handler
{
    private string $owner;

    public function __construct(string $owner)
    {
        $this->owner = $owner;
    }

    public function __invoke(Client $client, Message $message): void
    {
        if ($message->isInvite()) {
            if ($message->isFrom($this->owner)) {
                $client->write($message->accept());
            }
        }
    }
}
