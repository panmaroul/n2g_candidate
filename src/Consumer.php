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
 *An AMQP message function that saves
 * the filtered data from the queue to the
 * database
 */
function process_message(AMQPMessage $message){
        $messageBody = json_decode($message->body);
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        insertToDatabase($GLOBALS['db'],$messageBody);

        if ($message->body === 'quit') {
            $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
        }
}

/* The parameters of basic_consume
 * --------------------------------------------------------------------------------------------
 *   queue: Queue from where to get the messages
 *  consumer_tag: Consumer identifier
 *   no_local: Don't receive messages published by this consumer.
 *  no_ack: If set to true, automatic acknowledgement mode will be used by this consumer.
 *   exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
 *   nowait: If set, the server will not respond to the method. The client should not wait for a reply method.
 *            If the server could not complete the method it will raise a channel or connection exception.
 *   callback: A PHP Callback
 * --------------------------------------------------------------------------------------------
*/
$channel->basic_consume(getenv('QUEUE'), '', false, false, false, false, 'process_message');

/*
 * While the channel is consuming messages from the queue,
 * it will remain open.
 */
while ($channel ->is_consuming()) {
    try {
        $channel->wait();
    } catch (ErrorException $e) {
        echo  "<br>" . $e->getMessage();
    }
}

/*
 * If the channel stops consuming it will be closed.
 */
$channel->close();
$connection->close();
