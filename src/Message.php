<?php
declare(strict_types=1);

namespace Thirdplace\Irc;

final class Message
{
    /**
     * Formerly known as prefix
     */
    private ?string $source;

    private string $command;
    private array $parameters;

    private function __construct(
        ?string $source,
        string  $command,
        array   $parameters = []
    ) {
        $this->source = $source;
        $this->command = $command;
        $this->parameters = $parameters;
    }

    public static function fromString(string $line): self
    {
        $orig = $line;
        // todo: escape newlines
        $parts = explode(' ', trim($line));
        if (count($parts) === 1) {
            throw new IrcException(sprintf('Invalid message: "%s"', $orig));
        }

        $source = null;
        if ($parts[0][0] === ':') {
            $source = array_shift($parts);
        }
        $command = array_shift($parts);
        if ($command === null) {
            throw new IrcException(sprintf('Invalid message: "%s"', $orig));
        }
        return new self($source, $command, $parts);
    }

    public static function pass(string $parameter): self
    {
        return self::fromString("PASS $parameter");
    }

    public static function nick(string $nickname): self
    {
        return self::fromString("NICK $nickname");
    }

    public static function user(string $username, string $realname = null): self
    {
        $realname = $realname ?? $username;
        return self::fromString("USER $username 0 * :$realname");
    }

    public static function ison($nickname): self
    {
        $nicknames = implode(' ', (array)$nickname);
        return self::fromString("ISON $nicknames");
    }

    public static function notice($target, string $text): self
    {
        $targets = implode(',', (array)$target);

        return self::fromString("NOTICE $targets :$text");
    }

    public static function join($channel): self
    {
        $channelsString = implode(',', (array)$channel);

        return self::fromString("JOIN $channelsString");
    }

    public static function part($channel, string $reason = null): self
    {
        $channels = implode(',', (array)$channel);
        if ($reason) {
            return self::fromString("PART $channels :$reason");
        }
        return self::fromString("PART $channels");
    }

    public static function invite(string $nickname, string $channel)
    {
        return self::fromString("INVITE $nickname $channel");
    }

    public static function privmsg($target, string $text): self
    {
        // todo: ACTION (emote), PING (ctcp)
        $targets = implode(',', (array)$target);
        return self::fromString("PRIVMSG $targets :$text");
    }

    public static function mode(string $target, string $modestring): self
    {
        return self::fromString("MODE $target $modestring");
    }

    public static function topic(string $channel, string $topic = null)
    {
        if ($topic === null) {
            return self::fromString("TOPIC $channel");
        }
        if ($topic === '') {
            return self::fromString("TOPIC $channel :");
        }
        return self::fromString("TOPIC $channel :$topic");
    }

    public static function kick(string $channel, string $nickname, string $reason = null): self
    {
        $reason = $reason ?? $nickname;
        return self::fromString("KICK $channel $nickname :$reason");
    }

    public static function ban(string $channel, string $nick, string $reason = null): self
    {
        $reason = $reason ?? $nick;
        return self::fromString("BAN $channel $nick :$reason");
    }

    public static function quit(string $reason = null): self
    {
        $reason = $reason ?? '';
        return self::fromString("QUIT :$reason");
    }

    public function pong(): self
    {
        if (!$this->isPing()) {
            throw new IrcException('Tried to PONG a non-PING');
        }
        return self::fromString("PONG " . $this->parameters[0]);
    }

    public function reply(string $message): self
    {
        if (!$this->isPrivateMessage()) {
            throw new IrcException('Tried to reply to a non-PRIVMSG message');
        }

        if ($this->isChannelMessage()) {
            $to = $this->parameters[0];
        } else {
            $to = $this->from();
        }
        return self::privmsg($to, $message);
    }

    public function isPing(): bool
    {
        return $this->command === 'PING';
    }

    public function isJoin(): bool
    {
        return $this->command === 'JOIN';
    }

    public function isPart(): bool
    {
        return $this->command === 'PART';
    }

    public function isMode(): bool
    {
        return $this->command === 'MODE';
    }

    public function isKick(): bool
    {
        return $this->command === 'KICK';
    }

    public function isInvite(): bool
    {
        return $this->command === 'INVITE';
    }

    public function isDirectMessage(): bool
    {
        return $this->isPrivateMessage() && !$this->isChannelMessage();
    }

    public function isChannelMessage(): bool
    {
        return $this->isPrivateMessage() && $this->parameters[0][0] === '#';
    }

    /**
     * Get the message part of a PRIVMSG
     */
    public function getMessageParameter(): string
    {
        $parametersWithoutRecipient = array_slice($this->parameters, 1);
        $parametersTrimmed = array_map(fn($param) => ltrim($param, ':'), $parametersWithoutRecipient);
        return implode(' ', $parametersTrimmed);
    }

    /**
     * Accept an invite
     */
    public function accept(): self
    {
        if (!$this->isInvite()) {
            throw new IrcException('Tried to accept a non-invite message');
        }
        return self::join($this->parameters[1]);
    }

    /**
     * Direct message or channel message
     */
    public function isPrivateMessage(): bool
    {
        return $this->command === 'PRIVMSG';
    }

    public function isError(): bool
    {
        return $this->command === 'ERROR';
    }

    public function __toString()
    {
        $source = $this->source ? $this->source . ' ' : '';
        $parametersString = implode(' ', $this->parameters);

        return sprintf('%s%s %s', $source, $this->command, $parametersString);
    }

    public function isFrom(string $nick): bool
    {
        return $this->from() === $nick;
    }

    public function from(): string
    {
        if (preg_match('/^:(\S+)!/', $this->source, $m)) {
            return $m[1];
        }
        return '';
    }
}
