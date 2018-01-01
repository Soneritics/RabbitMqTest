<?php
require_once '../vendor/autoload.php';

$publisher = new \RabbitMq\RabbitMqPublisher(new \RabbitMq\RabbitMqConfig('guest', 'guest'));

$publisher->publish(['test' => ['result' => true]]);
