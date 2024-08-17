<?php
declare(strict_types=1);

namespace Thirdplace\Irc;

/**
 * Print html title from urls in channel messages
 */
final class Url implements Handler
{
    public function __invoke(Client $client, Message $message): void
    {
        if (!$message->isPrivateMessage()) {
            return;
        }

        $pattern = '/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)/';
        if (!preg_match($pattern, $message->getMessageParameter(), $m)) {
            return;
        }

        $url = $m[0];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        $result = curl_exec($ch);
        if ($result === false) {
            return;
        }
        if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $result, $matches)) {
            $title = html_entity_decode(trim($matches[1]), ENT_QUOTES);
            if ($title) {
                $client->write($message->reply($title));
            }
        }
    }
}
