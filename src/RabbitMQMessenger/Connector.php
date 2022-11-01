<?php


namespace KignOrg\RabbitMQMessenger;


use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class Connector extends Messenger
{
    protected QueueConfig $queueConfig;
    protected ?AMQPStreamConnection $connection;
    protected ?AMQPChannel $channel;

    public function __construct(QueueConfig $queueConfig, Parcelable $templateParcel)
    {
        parent::__construct($templateParcel);
        $this->queueConfig = $queueConfig;
    }

    /**
     * @throws Exception
     */
    public function openChannel(): AMQPChannel
    {
        $this->openStreamConnection();
        if ($this->isChannelOpen()) {
            return $this->channel;
        }

        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queueConfig->queue, $this->queueConfig->passive, $this->queueConfig->durable, $this->queueConfig->exclusive, $this->queueConfig->autoDelete);

        return $this->channel;
    }


    /**
     * @throws Exception
     */
    public function openStreamConnection(): AMQPStreamConnection
    {
        if (isset($this->connection) && $this->connection instanceof AMQPStreamConnection) {
            return $this->connection;
        }

        $this->connection = new AMQPStreamConnection(
            $this->queueConfig->host,
            $this->queueConfig->port,
            $this->queueConfig->user,
            $this->queueConfig->password,
            $this->queueConfig->vhost
        );

        return $this->connection;
    }

    /**
     * @throws Exception
     */
    public function closeStreamConnection()
    {
        $this->closeChannel();

        if ($this->isConnectionOpen()) {
            $this->connection->close();
            unset($this->connection);
        }
    }

    public function closeChannel()
    {
        if ($this->isChannelOpen()) {
            $this->channel->close();
            unset($this->channel);
        }
    }

    public function isChannelOpen(): bool
    {
        return (isset($this->channel) && $this->channel instanceof AMQPChannel);
    }

    public function isConnectionOpen(): bool
    {
        return (isset($this->connection) && $this->connection instanceof AMQPStreamConnection);
    }

    /**
     * @return Connector
     * @throws Exception
     */
    public function releaseResources(): static
    {
        $this->closeChannel();
        $this->closeStreamConnection();
        parent::releaseResources();
        return $this;
    }

    protected function hasCallbacks(): bool
    {
        return count($this->channel->callbacks) > 0;
    }

    protected function isMaximumReached(int $actual, int $max): bool
    {
        if (0 === $max) {
            return false;
        }

        return $actual >= $max;
    }
}
