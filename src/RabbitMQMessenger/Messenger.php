<?php


namespace KignOrg\RabbitMQMessenger;


class Messenger
{
    protected Message $templateMessage;

    public function __construct(Parcelable $templateParcel)
    {
        $this->templateMessage = new Message($templateParcel);
    }

    public function createBlankMessage(): Message
    {
        return $this->templateMessage->createBlank();
    }

    public function releaseResources(): static
    {
        unset($this->templateMessage);
        return $this;
    }
}
