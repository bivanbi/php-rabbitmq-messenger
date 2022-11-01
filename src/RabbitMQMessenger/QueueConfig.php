<?php


namespace KignOrg\RabbitMQMessenger;


class QueueConfig
{
    public string $driver = 'RabbitMQ';
    public string $queue;
    public string $host;
    public int $port;
    public string $user;
    public string $password;
    public string $vhost;

    public bool $passive = false;
    public bool $durable = false;
    public bool $exclusive = false;
    public bool $autoDelete = false;

    public function __construct(array $configArray = null)
    {
        if (is_array($configArray)) {
            $this->initWithArray($configArray);
        }
    }

    public function initWithArray(array $configArray): static
    {
        foreach ($configArray as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
        return $this;
    }
}
