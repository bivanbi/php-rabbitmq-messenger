<?php


namespace KignOrg\RabbitMQMessenger;


use Exception;
use KignOrg\RabbitMQMessenger\Exceptions\NoResponseReceivedException;
use KignOrg\RabbitMQMessenger\Exceptions\UnexpectedResponseReceivedException;
use PhpAmqpLib\Message\AMQPMessage;

class RPCClient extends Publisher
{
    /** @var UniqueID */
    protected UniqueID $correlationId;

    private array $responseQueue = [];

    private bool $isBlocking = true;

    private int $timeout = 10;

    public function __construct(QueueConfig $queueConfig, Parcelable $templateParcel)
    {
        parent::__construct($queueConfig, $templateParcel);
        $this->correlationId = UniqueID::getInstance();
    }

    public function setIsBlocking(bool $isBlocking): static
    {
        $this->isBlocking = $isBlocking;
        return $this;
    }

    public function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Send message and expect reply as outlined in https://www.rabbitmq.com/direct-reply-to.html
     *
     * @param Message $message
     * @return string|Message
     * @throws NoResponseReceivedException
     * @throws Exception
     */
    public function call(Message $message): string|Message
    {
        $correlationId = $this->correlationId->get();
        $messageProperties = $this->createMessageProperties($correlationId);

        $AMQPMessage = new AMQPMessage($message->getAMQPMessage()->getBody(), $messageProperties);
        $message = $this->templateMessage->createWithAMQPMessage($AMQPMessage);

        $this->startConsumer();
        $this->publish($message);

        if ($this->isBlocking !== true) {
            return $correlationId;
        }

        return $this->waitForReply($correlationId);
    }

    /**
     * @param string $correlationId
     * @return Message
     * @throws NoResponseReceivedException
     */
    public function waitForReply(string $correlationId): Message
    {
        $this->expectReply($correlationId);
        $this->correlationId->release($correlationId);

        if ($this->isResponseReceived($correlationId)) {
            return $this->getResponseAndReleaseRelatedResources($correlationId);
        }

        throw new NoResponseReceivedException("No reply received to request", 7153262);
    }


    private function createMessageProperties(string $correlationId): array
    {
        return [
            'correlation_id' => $correlationId,
            'reply_to' => Contract::DIRECT_RPC_REPLY_TO_CHANNEL,
            'delivery_mode' => Contract::PERSISTENT_MESSAGE_DELIVERY_MODE
        ];
    }

    /**
     * @throws Exception
     */
    private function startConsumer()
    {
        $this->openChannel();

        $this->channel->basic_consume(
            Contract::DIRECT_RPC_REPLY_TO_CHANNEL,
            '',
            false,
            true,
            false,
            false,
            [$this, 'onResponse']
        );
    }

    /**
     * @param string $correlationId
     * @param int $timeout
     * @return Message|null
     */
    public function poll(string $correlationId, int $timeout = 0): ?Message
    {
        $this->channel->wait(null, true, $timeout);
        if ($this->isResponseReceived($correlationId)) {
            return $this->getResponseAndReleaseRelatedResources($correlationId);
        }
        print __FUNCTION__.": no response received yet\n";
        return null;
    }

    /**
     * @param $correlationId
     * @return Message
     */
    private function getResponseAndReleaseRelatedResources($correlationId): Message
    {
        /** @var Message $response */
        $response = $this->responseQueue[$correlationId];
        unset($this->responseQueue[$correlationId]);
        $this->correlationId->release($correlationId);
        return $response;
    }

    /**
     * @param string $correlationId
     * @return void
     */
    private function expectReply(string $correlationId): void
    {
        while (!$this->isResponseReceived($correlationId) && $this->channelHasCallbacks()) {
            $this->channel->wait(null, null, $this->timeout);
        }
    }

    private function isResponseReceived(string $correlationId): bool
    {
        return array_key_exists($correlationId, $this->responseQueue);
    }

    private function channelHasCallbacks(): bool
    {
        return (0 < count($this->channel->callbacks));
    }

    /**
     * @param AMQPMessage $response
     * @throws UnexpectedResponseReceivedException
     */
    public function onResponse(AMQPMessage $response)
    {
        $correlationId = $response->get('correlation_id');

        if ($this->correlationId->exists($correlationId)) {
            $this->responseQueue[$correlationId] = $this->templateMessage->createWithAMQPMessage($response);
        } else {
            throw new UnexpectedResponseReceivedException('Unexpected response received', $this->queueConfig['queue'], $correlationId, $response);
        }
    }

    public function releaseResources(): static
    {
        $this->correlationId->releaseAll();
        $this->responseQueue = [];
        parent::releaseResources();
        return $this;
    }

}
