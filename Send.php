<?php
require_once '../vendor/autoload.php';
require_once 'RabbitMq.php';

$publisher = new RabbitMqPublisher(new RabbitMqConfig('guest', 'guest'));

$publisher->publish(['test' => ['result' => true]]);
