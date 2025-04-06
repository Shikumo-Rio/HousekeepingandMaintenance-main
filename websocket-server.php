<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketServer implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        // Check if the message is a task assignment notification
        if (isset($data['type']) && $data['type'] === 'task_assignment') {
            foreach ($this->clients as $client) {
                $client->send(json_encode([
                    'type' => 'task_assignment',
                    'task_id' => $data['task_id'],
                    'task_details' => $data['task_details'],
                    'assigned_to' => $data['assigned_to']
                ]));
            }
        } elseif (isset($data['type']) && $data['type'] === 'notification') {
            foreach ($this->clients as $client) {
                $client->send(json_encode([
                    'type' => 'notification',
                    'title' => $data['title'],
                    'message' => $data['message']
                ]));
            }
        } else {
            // Broadcast other messages
            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    $client->send($msg);
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

use Ratchet\App;

$app = new App('localhost', 8080);
$app->route('/chat', new WebSocketServer, ['*']);
$app->run();
