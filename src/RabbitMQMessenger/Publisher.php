<?php


namespace KignOrg\RabbitMQMessenger;


use Exception;

class Publisher extends Connector
{
    /**
     * @param Message $message
     * @return Connector
     * @throws Exception
     */
    public function publishSingleMessage(Message $message): Connector
    {
        $this->openStreamConnection();
        $this->publish($message);
        $this->releaseResources();
        return $this;
    }

    /**
     * @throws Exception
     */
    public function publish(Message $message): Connector
    {
        $AMQPMessage = $message->getAMQPMessage();
        $this->openChannel();
        $this->channel->basic_publish($AMQPMessage, '', $this->queueConfig->queue);
        return $this;
    }
}
