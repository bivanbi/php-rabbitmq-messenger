<?php


namespace KignOrg\RabbitMQMessenger;


class StringParcelable implements Parcelable
{
    private ?string $message;

    public function __construct(string $message = null)
    {
        $this->message = $message;
    }

    public function createBlank(): Parcelable
    {
        return new StringParcelable();
    }

    public function createWithString(string $string): Parcelable
    {
        return new StringParcelable($string);
    }

    public function toString(): string
    {
        return $this->message;
    }

    public function receiveString(string $message): Parcelable
    {
        $this->message = $message;
        return $this;
    }
}
