<?php
declare(strict_types=1);

namespace Thirdplace\Irc;

final class Client
{
    private array $config;

    /**
     * @var Handler[]
     */
    private array $handlers;

    /**
     * @var callable[]
     */
    private array $tickHandlers;

    /**
     * @var resource
     */
    private $socket = null;

    /**
     * @var false|resource
     */
    private $pipe;

    public function __construct(array $config)
    {
        $defaults = [
            'protocol'  => 'tls',
            'server'    => 'irc.libera.chat',
            'port'      => 6697,
            'channels'  => [],
            'nick'      => null,
            'user'      => null,
            'password'  => null,
            // twitch oauth token
            'token'     => null,
            'auth'      => null,
            'pipe_file' => null,
            'output_log' => true,
        ];

        $config = array_merge($defaults, $config);

        if (
            ! $config['nick']
            || count($config) !== count($defaults)
            || (! in_array($config['protocol'], ['tcp', 'tls']))
            || (! in_array($config['auth'], [null, 'nickserv', 'sasl', 'twitch']))
        ) {
            throw new IrcException('Illegal config detected');
        }

        $this->config = $config;

        $this->handlers = [
            new PingHandler(),
            new ReconnectHandler(),
        ];

        $this->tickHandlers = [];

        if ($config['pipe_file']) {
            if (!file_exists($config['pipe_file'])) {
                if (!posix_mkfifo($config['pipe_file'], 0777)) {
                    throw new IrcException('mkfifo');
                }
            }
            $this->pipe = fopen($config['pipe_file'], 'r+'); // w+ ?
            if ($this->pipe === false) {
                throw new IrcException('fopen pipe');
            }
            stream_set_blocking($this->pipe, false);
        }
    }

    public function start(): void
    {
        $this->connect();

        if (stream_set_blocking($this->socket, false) === false) {
            throw new IrcException('stream_set_blocking');
        }

        if ($this->config['channels']) {
            $this->write(Message::join($this->config['channels']));
        }

        while (true) {
            $meta = stream_get_meta_data($this->socket);
            if ($meta['eof'] === true) {
                throw new IrcException('eof');
            }
            if ($meta['timed_out'] === true) {
                throw new IrcException('timed_out');
            }

            foreach ($this->tickHandlers as $tickHandler) {
                $tickHandler($this);
            }

            if ($this->config['pipe_file']) {
                $buf = fread($this->pipe, 512);
                if ($buf !== false && $buf !== '') {
                    $this->write(trim($buf));
                }
            }

            $line = $this->read();
            $line2 = $line;
            if ($line === false || $line === '') {
                usleep(1000 * 50); // 50ms
                continue;
            }

            $this->log(trim($line));

            try {
                $message = Message::fromString($line2);
            } catch (IrcException $e) {
                $this->log("IrcException: " . $e->getMessage());
                continue;
            }

            foreach ($this->handlers as $handler) {
                $handler($this, $message);
            }
        }
    }

    public function restart(): void
    {
        $this->log('Restart');
        $this->disconnect();
        $this->start();
    }

    public function connect(): void
    {
        $this->log('Connect');
        $address = sprintf('%s://%s:%s', $this->config['protocol'], $this->config['server'], $this->config['port']);
        $this->socket = stream_socket_client($address);
        if ($this->socket === false) {
            throw new IrcException('stream_socket_client');
        }
        if (stream_set_timeout($this->socket, 60 * 10) === false) {
            throw new IrcException('stream_set_timeout');
        }

        switch ($this->config['auth']) {
            case 'nickserv':
                $this->authenticateWithNickServ();
                break;
            case 'sasl':
                $this->authenticateWithSasl();
                break;
	        case 'twitch':
                $this->authenticateWithTwitch();
                break;
            default:
                $this->register();
                break;
        }
    }

    public function disconnect(): void
    {
        $this->log('Disconnect');
        if (fclose($this->socket) === false) {
            throw new IrcException('disconnect');
        }
    }

    public function addHandler(callable $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function addTickHandler(int $seconds, callable $fn)
    {
        $this->tickHandlers[] = new TickHandler($seconds, $fn);
    }

    private function read()
    {
        $line = fgets($this->socket, 512);
        if ($line === false || $line === '') {
            return false;
        }
        return $line;
    }

    /**
     * @param Message|string $message
     */
    public function write($message): void
    {
        $this->log($message);
        $n = fwrite($this->socket, "$message\n");
        if ($n === false) {
            throw new IrcException('Unable to write to socket');
        }
    }

    private function authenticateWithSasl(): void
    {
        if (! $this->config['password']) {
            throw new IrcException('Missing password for sasl');
        }
        $this->write('CAP REQ :sasl');
        $this->register();
        $this->write('AUTHENTICATE PLAIN');
        $this->write(sprintf("AUTHENTICATE %s", base64_encode(sprintf("\0%s\0%s", $this->config['user'], $this->config['password']))));
        $this->write('CAP END');
    }

    private function authenticateWithNickServ(): void
    {
        $this->register();
        $identify = sprintf('IDENTIFY %s %s', $this->config['nick'], $this->config['password']);
        $this->write(Message::privmsg('NickServ', $identify));
    }

    private function authenticateWithTwitch(): void
    {
        $this->write(Message::pass(sprintf("oauth:%s", $this->config['token'])));
        $this->write(Message::nick($this->config['nick']));
    }

	private function register(): void
    {
        $this->write(Message::user($this->config['user'] ?? $this->config['nick']));
        $this->write(Message::nick($this->config['nick']));
    }

    public function log($line): void
    {
        if ($this->config['output_log'] === false) {
            return;
        }
        $now = new \DateTime();
        printf("[%s] %s\n", $now->format('Y-m-d H:i:s'), $line);
    }
}
