<?php


namespace KignOrg\RabbitMQMessenger;


use Exception;
use KignOrg\RabbitMQMessenger\Exceptions\ConsumerException;
use KignOrg\RabbitMQMessenger\Exceptions\MessageException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends Connector
{
    /** @var callable */
    private mixed $messageReceiver;
    private bool $isBlocking = true;
    private string $consumerTag;

    public function __construct(QueueConfig $queueConfig, Parcelable $templateParcel)
    {
        parent::__construct($queueConfig, $templateParcel);
    }

    public function setIsBlocking(bool $isBlocking): Consumer
    {
        $this->isBlocking = $isBlocking;
        return $this;
    }

    public function setOnMessageReceivedCallback(callable $receiver): Consumer
    {
        $this->messageReceiver = $receiver;
        return $this;
    }

    /**
     * @param string|null $consumerTag
     * @return string consumerTag
     * @throws ConsumerException
     * @throws Exception
     */
    public function startConsumer(string $consumerTag = null): string
    {
        $this->exceptOnConsumerAlreadyRegistered();
        $this->exceptOnNoMessageReceiver();
        $this->openChannel();
        $consumerTag = $this->channel->basic_consume($this->queueConfig->queue, $consumerTag, false, true, false, false, [$this, 'onConsume']);
        $this->consumerTag = $consumerTag;

        if (false === $this->isBlocking) {
            return $consumerTag;
        }

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        return $consumerTag;
    }

    /**
     * @param int $maxCallbackCount
     * @param int $timeout
     */
    public function blockingPoll(int $maxCallbackCount = 1, int $timeout = 0)
    {
        $actualCallbackCount = 0;
        while ($this->hasCallbacks() && !$this->isMaximumReached($actualCallbackCount, $maxCallbackCount)) {
            $this->channel->wait(null, false, $timeout);
            if ($maxCallbackCount) {
                $actualCallbackCount++;
            }
        }
    }

    /**
     * When in non-blocking mode, this method should be called regularly
     * to give consumer CPU time to actually receive messages
     * @param int $timeout timeout in seconds, default: 0 = wait forever
     * @return Consumer
     * @throws ConsumerException
     */
    public function poll(int $timeout = 0): static
    {
        $this->exceptOnNoConsumerRegistered();
        $this->channel->wait(null,true, $timeout);
        return $this;
    }

    /**
     * @param array|null $allowedMethods
     * @param bool $nonBlocking
     * @param int $timeout
     * @return mixed
     */
    public function channelWait(array $allowedMethods = null, bool $nonBlocking = false, int $timeout = 0): mixed
    {
        return $this->channel->wait($allowedMethods, $nonBlocking, $timeout);
    }

    public function cancelConsumer(): static
    {
        if (isset($this->consumerTag)) {
            $this->channel->basic_cancel($this->consumerTag);
            unset($this->consumerTag);
        }
        return $this;
    }

    public function releaseResources(): static
    {
        $this->cancelConsumer();
        unset($this->messageReceiver);
        parent::releaseResources();
        return $this;
    }

    /**
     * @param AMQPMessage $AMQPMessage
     * @return $this|mixed
     */
    public function onConsume(AMQPMessage $AMQPMessage): mixed
    {
        $message = $this->templateMessage->createWithAMQPMessage($AMQPMessage);
        return call_user_func($this->messageReceiver, $this->queueConfig->queue, $message);
    }

    /**
     * @param Message $inReply
     * @param Message $message
     * @return Consumer
     * @throws MessageException
     */
    public function sendReply(Message $inReply, Message $message): Consumer
    {
        $inReply->exceptOnMessageNotRepliable();

        /** @var AMQPChannel $channel */
        $channel = $inReply->getDeliveryChannel();
        $replyTo = $inReply->getReplyTo();
        $channel->basic_publish($message->getAMQPMessage(), '', $replyTo);
        return $this;
    }

    /**
     * @throws ConsumerException
     */
    private function exceptOnNoMessageReceiver()
    {
        if (!is_callable($this->messageReceiver)) {
            throw new ConsumerException("No message receiver has been registered");
        }
    }

    /**
     * @throws ConsumerException
     */
    private function exceptOnConsumerAlreadyRegistered()
    {
        if (isset($this->consumerTag)) {
            throw new ConsumerException("Consumer '$this->consumerTag' already registered");
        }
    }

    /**
     * @throws ConsumerException
     */
    private function exceptOnNoConsumerRegistered()
    {
        if (!isset($this->consumerTag)) {
            throw new ConsumerException("No consumer registered");
        }
    }
}
