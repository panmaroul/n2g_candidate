<?php
include (__DIR__.'/Connection.php');
include (__DIR__.'/PrepareMessage.php');
require dirname(__DIR__).'/vendor/autoload.php';
use PhpAmqpLib\Message\AMQPMessage;

/*
 * Loading the .env file where all the
 * credentials are stored
 */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/../");
$dotenv->load();

/*
 * Connecting to RabbitMq and the database
 */
$connection = connectToRabbit();
$channel = rabbitChannel();
$db = connectToDatabase();

/*
 * While the channel is open, the publisher will keep on
 * receiving messages from the API and publishing them to
 * the given exchange.
 */
while($channel->is_open()){
    $content = file_get_contents("https://7hzd0txqhj.execute-api.eu-west-1.amazonaws.com/dev/results");
    $result  = json_decode($content);
    $channel->queue_bind(getenv('QUEUE'), getenv('EXCHANGE'),routingKey($result)); //routingKey function in Prepare Message script
    $messageBody = json_encode(restMessage($result)); //restMessage function in Prepare Message script

    /*
     * Creating the message to publish to the exchange.
     * The message to be published will be JSON type.
     * Delivery mode persistent along with the durable means that
     * the messages are written on the disks and are recoverable.
     */
    $message = new AMQPMessage($messageBody, array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
    $channel->basic_publish($message,getenv('EXCHANGE'), routingKey($result));
}

$channel->close();
try {
    $connection->close();
} catch (Exception $e) {
}