<?php


namespace KignOrg\RabbitMQMessenger;


interface Parcelable
{
    public function createBlank(): Parcelable;
    public function createWithString(string $string): Parcelable;

    public function receiveString(string $message): Parcelable;
    public function toString(): string;
}