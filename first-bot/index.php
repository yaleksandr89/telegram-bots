<?php

if (file_exists('../config.php')) {
    include_once '../config.php';
} else {
    die('Please, created config file.');
}

$client = new GuzzleHttp\Client([
    'base_uri' => 'https://api.telegram.org/bot' . FIRST_BOT_TOKEN . '/'
]);

try {
    /** DON'T USE AN INFINITE LOOP ON A REAL SERVER! */
    while (true) {
        $params = [];
        if (isset($lastUpdate)) {
            $params = [
                'query' => [
                    'offset' => $lastUpdate + 1,
                ],
            ];
        }

        // >>> getUpdates
        $methodGetUpdates = $client->get('getUpdates', $params);
        $contentsGetUpdates = $methodGetUpdates->getBody()->getContents();
        // getUpdates <<<

        // >>> sendMessage
        $preparedRequestInfo = json_decode($contentsGetUpdates, true, 512, JSON_THROW_ON_ERROR);
        if (count($preparedRequestInfo['result']) > 0) {
            foreach ($preparedRequestInfo['result'] as $key => $item) {
                echo $item['message']['text'] . "\n";
                file_put_contents(__DIR__ . '/logs-messages.txt', print_r($preparedRequestInfo['result'][$key], true), FILE_APPEND);
                $lastUpdate = $item['update_id'];
                $responseText = 'Вы написали: ' . $item['message']['text'];

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
