<?php


namespace KignOrg\RabbitMQMessenger\Exceptions;


use Exception;
use PhpAmqpLib\Message\AMQPMessage;

class UnexpectedResponseReceivedException extends Exception
{
    public ?string $queue;
    public ?string $correlationId;
    public AMQPMessage $response;

    /**
     * @param string $message
     * @param string|null $queue
     * @param string|null $correlationId
     * @param AMQPMessage $response
     */
    public function __construct(string $message, ?string $queue, ?string $correlationId, AMQPMessage $response)
    {
        parent::__construct($message);
        $this->queue = $queue;
        $this->correlationId = $correlationId;
        $this->response = $response;
    }
}
