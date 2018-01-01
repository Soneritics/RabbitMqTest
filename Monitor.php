<?php
require_once '../vendor/autoload.php';

/**
 * @param \PhpAmqpLib\Message\AMQPMessage $message
 */
$processMessage = function($message)
{
    $content = json_decode($message->body);
    print_r($content);
    echo PHP_EOL;

    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

    // Send a message with the string "quit" to cancel the consumer.
    if ($message->body === 'quit') {
        $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
    }
};

$consumer = new \RabbitMq\RabbitMqConsumer(new \RabbitMq\RabbitMqConfig('guest', 'guest'));
$consumer->subscribe('test-consumer', $processMessage);
$consumer->listen();
