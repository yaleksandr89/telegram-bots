<?php

use GuzzleHttp\Client;

/**
 * @var Client $client
 */

if (file_exists('../config.php')) {
    include_once '../config.php';
} else {
    die('Please, created config file.');
}

function addToLogs(mixed $message)
{
    file_put_contents(__DIR__ . '/logs-messages.txt', print_r($message, true), FILE_APPEND);
}

try {
    /** DON'T USE AN INFINITE LOOP ON A REAL SERVER! */
    while (true) {
        // >>> getUpdates
        $params = [];
        if (isset($lastUpdate)) {
            $params = [
                'query' => [
                    'offset' => $lastUpdate + 1,
                ],
            ];
        }
        $methodGetUpdates = $client->get('getUpdates', $params);
        $contentsGetUpdates = $methodGetUpdates->getBody()->getContents();
        // getUpdates <<<

        // >>> sendMessage
        $preparedRequestInfo = json_decode($contentsGetUpdates, true, 512, JSON_THROW_ON_ERROR);
        if (count($preparedRequestInfo['result']) > 0) {
            foreach ($preparedRequestInfo['result'] as $key => $item) {
                echo $item['message']['text'] . "\n"; // Output to console
                addToLogs($preparedRequestInfo['result'][$key]); // Output to logs

                $lastUpdate = $item['update_id'];
                $responseText = "Привет {$item['message']['from']['first_name']}. Ты написал: '{$item['message']['text']}'";

                $methodSendMessage = $client->get('sendMessage', [
                    'query' => [
                        'chat_id' => 266222035,
                        'text' => $responseText
                    ],
                ]);
            }
        }
        // sendMessage <<<
        sleep(2);
    }
} catch (Throwable $e) {
    dd($e->getMessage());
}
