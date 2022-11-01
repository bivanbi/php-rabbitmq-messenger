<?php


namespace KignOrg\RabbitMQMessenger;

interface MessageCodec
{
    public function encode($data): string;
    public function decode(string $message);
}