# PHP IRC Client
Forked from [thirdplace/irc](https://git.sr.ht/~thirdplace/irc)

A very basic IRC library and client (bot).

For hobby purposes, not production ready.

Unclear if the network code is any good.

## Changes
* Make `Message::from()` public
* Update `Message::from()` regex to support nicknames with dashes
* Make output logging optional
* Add option to accept invalid SSL certificates

## Tutorial

Install:

    composer require thirdplace/irc:dev-main

Examples:
```php
// non-tls
$config = [
    'server'    => 'irc.libera.chat',
    'protocol'  => 'tcp',
    'port'      => 6667,
    'nick'      => 'botson45',
    'channels'  => ['#test'],
];
$client = new \Thirdplace\Irc\Client($config);
$client->start();
```

```php
// tls
$libera = [
    'server'    => 'irc.libera.chat',
    'protocol'  => 'tls',
    'port'      => 6697,
    'nick'      => 'botson45',
    'channels'  => ['#test'],
];
$client = new \Thirdplace\Irc\Client($config);
$client->start();
```

```php
// tls with SASL authentication
$libera2 = [
    'server'    => 'irc.libera.chat',
    'protocol'  => 'tls',
    'port'      => 6697,
    'nick'      => 'botson45',
    'user'      => 'botson45',
    'auth'      => 'sasl',
    'password'  => 'REPLACE ME',
    'channels' => ['#test'],
];
$client = new \Thirdplace\Irc\Client($config);
$client->start();
```

```php
// tls with twitch authentication (oauth)
$twitch = [
    'server'    => 'irc.chat.twitch.tv',
    'protocol'  => 'tls',
    'port'      => 6697,
    'nick'      => 'botson45',
    'auth'      => 'twitch',
    'token'     => 'REPLACE ME',
    'channels'  => ['#hasanbi'],
];
$client = new \Thirdplace\Irc\Client($config);
$client->start();
```

Run unit tests:

```shell
./vendor/bin/phpunit --bootstrap=vendor/autoload.php ./test
```

## How-to

### How to create a command

```php
// Respond to !hello command with 'world'
$client->addHandler(function(Client $client, Message $message) {
    if (
        $message->isChannelMessage()
        && preg_match('/^!hello/', $message->getMessageParameter())
    ) {
        $client->write($message->reply('world'));
    }
});
```

### How to run handler each 10 seconds

```php
// Write a tick message to #test3 each 10 seconds
$client->addTickHandler(10, function(Client $client) {
    $client->write(Message::privmsg('#test3', 'tick!'));
});
```

### How to send raw text to server

Create client wite `pipe_file` config:

```php
$config = [
    'server'    => 'irc.libera.chat',
    'protocol'  => 'tcp',
    'port'      => 6667,
    'nick'      => 'botson45',
    'channels'  => ['#test3'],
    'pipe_file' => '/tmp/irc',
];
$client = new \Thirdplace\Irc\Client($config);
$client->start();
```

Write to it from shell:

```shell
echo "PRIVMSG #test3 :hello world" > /tmp/irc
```

## Explanation

The code base is small. Start with `src/Client.php` to get a feel.

The majority of the irc logic is in `Message.php`. The client is in `Client.php`.
The `src/handlers` folder contains code to be invoked based off of passing irc messages.

## Reference

Client config:
```php
$defaults = [
    'server'    => 'irc.libera.chat',
    'protocol'  => 'tls', // ['tcp', 'tls']
    'port'      => 6697,
    'auth'      => null, // [null, 'nickserv', 'sasl', 'twitch']
    'nick'      => null,
    'user'      => null,
    'password'  => null, // for NickServer or SASL
    'token'     => null, // oauth auth token (twitch.tv et al)
    'channels'  => [], // List of channels to join
    'pipe_file' => null, // Write raw text to server
    'output_log' => true, // echo output
    'timeout'   => 30, // Connection timeout
    'verify_peer' => true, // Verify SSL certificate
];
```

Handler interface:
```php
interface Handler
{
    public function __invoke(Client $client, Message $message): void;
}
```

Base exception:

```php
final class IrcException extends \Exception
{
}
```


https://www.rfc-editor.org/rfc/rfc1459.txt

https://modern.ircdocs.horse/
