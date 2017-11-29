<?php
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class RabbitMqConfig
 */
class RabbitMqConfig
{
    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $host = 'localhost';

    /**
     * @var int
     */
    private $port = 5672;

    /**
     * @var string
     */
    private $vhost = '/';

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * RabbitMQConfig constructor.
     * @param string $user
     * @param string $password
     */
    public function __construct(string $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @return RabbitMqConfig
     */
    public function setUser(string $user): RabbitMqConfig
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return RabbitMqConfig
     */
    public function setPassword(string $password): RabbitMqConfig
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return RabbitMqConfig
     */
    public function setHost(string $host): RabbitMqConfig
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return RabbitMqConfig
     */
    public function setPort(int $port): RabbitMqConfig
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return string
     */
    public function getVhost(): string
    {
        return $this->vhost;
    }

    /**
     * @param string $vhost
     * @return RabbitMqConfig
     */
    public function setVhost(string $vhost): RabbitMqConfig
    {
        $this->vhost = $vhost;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     * @return RabbitMqConfig
     */
    public function setDebug(bool $debug): RabbitMqConfig
    {
        $this->debug = $debug;
        return $this;
    }
}

/**
 * Class RabbitMqConnectionParameters
 */
class RabbitMqConnectionParameters
{
    /**
     * @var string
     */
    private $exchange = 'router';

    /**
     * @var string
     */
    private $queue = 'messages';

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @param string $exchange
     * @return RabbitMqConnectionParameters
     */
    public function setExchange(string $exchange): RabbitMqConnectionParameters
    {
        $this->exchange = $exchange;
        return $this;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @param string $queue
     * @return RabbitMqConnectionParameters
     */
    public function setQueue(string $queue): RabbitMqConnectionParameters
    {
        $this->queue = $queue;
        return $this;
    }
}

/**
 * Class RabbitMQ
 */
abstract class RabbitMq
{
    /**
     * @var RabbitMqConfig
     */
    private $config;

    /**
     * @var RabbitMqConnectionParameters
     */
    protected $connectionParameters;

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * RabbitMQ constructor.
     * @param RabbitMqConfig $config
     * @param RabbitMqConnectionParameters $connectionParameters
     */
    public function __construct(RabbitMqConfig $config, RabbitMqConnectionParameters $connectionParameters = null)
    {
        $this->config = $config;
        $this->connectionParameters = $connectionParameters ?? new RabbitMqConnectionParameters();

        $this->connect();
    }

    /**
     * Connect to RabbitMq
     */
    private function connect(): void
    {
        $this->connection = new AMQPStreamConnection($this->config->getHost(), $this->config->getPort(), $this->config->getUser(), $this->config->getPassword(), $this->config->getVhost());
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->connectionParameters->getQueue(), false, true, false, false);
        $this->channel->exchange_declare($this->connectionParameters->getExchange(), 'direct', false, true, false);
        $this->channel->queue_bind($this->connectionParameters->getQueue(), $this->connectionParameters->getExchange());
    }

    /**
     * Destructor, closes the channel and connection.
     */
    public function __destruct()
    {
        try { $this->channel->close(); } catch (\Exception $e) { }
        try { $this->connection->close(); } catch (\Exception $e) { }
    }
}

/**
 * Class RabbitMqConsumer
 */
class RabbitMqConsumer extends RabbitMq
{
    /**
     * @param string $consumerTag
     */
    public function subscribe(string $consumerTag): void
    {
        $this->channel->basic_consume($this->connectionParameters->getQueue(), $consumerTag, false, false, false, false, 'process_message');
    }

    /**
     * Listen to the messages on the bus
     */
    public function listen(): void
    {
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }
}

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class RabbitMqPublisher
 */
class RabbitMqPublisher extends RabbitMq
{
    public function publish($message): void
    {
        $message = new AMQPMessage(json_encode($message), array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $this->channel->basic_publish($message, $this->connectionParameters->getExchange());
    }
}


