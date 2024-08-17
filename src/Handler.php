<?php
declare(strict_types=1);

namespace Thirdplace\Irc;

interface Handler
{
    public function __invoke(Client $client, Message $message): void;
}
