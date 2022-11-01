<?php


namespace KignOrg\RabbitMQMessenger;


use KignOrg\RabbitMQMessenger\Exceptions\MessageException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class Message
{
    const REPLY_TO_ATTRIBUTE = 'reply_to';
    const CORRELATION_ID_ATTRIBUTE = 'correlation_id';
    const DELIVERY_CHANNEL_ATTRIBUTE = 'channel';

    private Parcelable $parcelTemplate;
    private AMQPMessage $AMQPMessage;

    public function __construct(Parcelable $parcelTemplate, AMQPMessage $AMQPMessage = null)
    {
        $this->parcelTemplate = $parcelTemplate;
        $this->AMQPMessage = $AMQPMessage ?? new AMQPMessage();
    }

    public function createBlank(): Message
    {
        $parcel = $this->parcelTemplate->createBlank();
        return new Message($parcel);
    }

    public function createWithAMQPMessage(AMQPMessage $AMQPMessage): Message
    {
        $parcel = $this->parcelTemplate->createBlank();
        return new Message($parcel, $AMQPMessage);
    }

    /**
     * @return Parcelable
     */
    public function createBlankParcel(): Parcelable
    {
        return $this->parcelTemplate->createBlank();
    }

    public function setStringMessage(string $message): Message
    {
        $parcel = $this->parcelTemplate->createWithString($message);
        $this->AMQPMessage->setBody($parcel->toString());
        return $this;
    }

    public function getReplyMessage(Parcelable $parcel): Message
    {
        $reply = new AMQPMessage(
            $parcel->toString(),
            ['correlation_id' => $this->getCorrelationId()]
        );
        return new Message($this->parcelTemplate, $reply);
    }

    /**
     * @param Parcelable $parcelTemplate
     * @return Message
     */
    public function setParcelTemplate(Parcelable $parcelTemplate): Message
    {
        $this->parcelTemplate = $parcelTemplate;
        return $this;
    }
    public function setParcel(Parcelable $parcel): Message
    {
        $this->AMQPMessage->setBody($parcel->toString());
        return $this;
    }

    public function getParcel(): Parcelable
    {
        $payload = $this->AMQPMessage->getBody();
        return $this->parcelTemplate->createWithString($payload);
    }

    public function getAMQPMessage(): AMQPMessage
    {
        return $this->AMQPMessage;
    }

    /**
     * @return bool
     */
    public function hasReplyTo(): bool
    {
        return $this->AMQPMessage->has(self::REPLY_TO_ATTRIBUTE);
    }

    /**
     * @return mixed|AMQPChannel
     */
    public function getReplyTo(): mixed
    {
        return $this->AMQPMessage->get(self::REPLY_TO_ATTRIBUTE);
    }

    /**
     * @return bool
     */
    public function hasCorrelationId(): bool
    {
        return $this->AMQPMessage->has(self::CORRELATION_ID_ATTRIBUTE);
    }

    /**
     * @return mixed|AMQPChannel
     */
    public function getCorrelationId(): mixed
    {
        return $this->AMQPMessage->get(self::CORRELATION_ID_ATTRIBUTE);
    }

    /**
     * @throws MessageException
     */
    public function exceptOnMessageNotRepliable(): void
    {
        if (!$this->isRepliable()) {
            throw new MessageException("Message is not repliable");
        }
    }

    /**
     * @return bool
     */
    public function isRepliable(): bool
    {
        return $this->hasReplyTo() && $this->hasCorrelationId();
    }

    /**
     * @return mixed
     */
    public function getDeliveryChannel(): mixed
    {
        return $this->AMQPMessage->delivery_info[self::DELIVERY_CHANNEL_ATTRIBUTE];
    }
}
