<?php


namespace KignOrg\RabbitMQMessenger;


class Contract
{
    const DIRECT_RPC_REPLY_TO_CHANNEL = 'amq.rabbitmq.reply-to';

    const NON_PERSISTENT_MESSAGE_DELIVERY_MODE = 0;
    const PERSISTENT_MESSAGE_DELIVERY_MODE = 2;

    const PING_REQUEST = 'ping';
    const PING_REPLY = 'pong';
}