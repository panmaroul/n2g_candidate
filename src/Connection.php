<?php
require __DIR__.'/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;

/*
 * Loading the .env file where all the
 * credentials are stored
 */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/../");
$dotenv->load();

/*
 * Function to create an AMPQP stream connection
 */
function connectToRabbit(){
    return $connection = new AMQPStreamConnection(
        getenv('HOSTNAME'),
        getenv('PORT'),
        getenv('USER'),
        getenv('PASSWORD'),
        getenv('VHOST'),
        false,
        'AMQPLAIN',
        null,
        'en_US',
        3.0,
        120.0,
        null,
        true,
        60.0
    );
}

/*
 * Function to declare the queue and the exchange.
 * It returns the channel.
 */
function rabbitChannel(){
    $connection = connectToRabbit();
    $channel = $connection->channel();
    $channel->queue_declare(getenv('QUEUE'), true, true, false, false,new AMQPTable(array(
        "x-message-ttl" => 60000 //messages will remain 60 seconds in the queue before they expire.
    )));
    $channel->exchange_declare(getenv('EXCHANGE'), AMQPExchangeType::TOPIC, true, true, false);
    return $channel;
}

/*
 * Function to connect to the database. Due to some
 * technical issues i created a local database in my system
 * and stored the values there.
 */
function connectToDatabase(){
    $hostname = getenv('DB_HOST');
    $db = getenv('DB_NAME');
    $username = getenv('DB_USER');
    $password = getenv('DB_PASS');
    try{
        $conn = new PDO("mysql:host=$hostname;dbname=$db;port=3306",$username,$password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connected successfully";
        return $conn;
    }catch (PDOException $e){
        echo   "<br>" . $e->getMessage();
        return null;
    }
}

/*
 * Function to insert the values to the database.
 */
function insertToDatabase($conn,$messageBody){
    try{
        $timestamp = date('Y-m-d h:i:s', $messageBody->timestamp / 1000);
        $sql = "INSERT INTO data (value,timestamp)
        VALUES ('$messageBody->value','$timestamp')";
        $conn->exec($sql);
    }catch (PDOException $e){
        echo   "<br>" . $e->getMessage();
    }

}
