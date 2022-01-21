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

try {
    // >>> getMe
    $methodGetMe = $client->get('getMe');
    $contentsGetMe = $methodGetMe->getBody()->getContents();
    //echo $contentsGetMe->getBody();
    //dump(json_decode($contentsGetMe, true, 512, JSON_THROW_ON_ERROR));
    // getMe <<<

    // >>> getUpdates
    $methodGetUpdates = $client->get('getUpdates', [
        'query' => [
            'offset' => 111342304,
        ],
    ]);
    $contentsGetUpdates = $methodGetUpdates->getBody()->getContents();
    //echo $methodGetUpdates->getBody();
    // getUpdates <<<

    // >>> sendMessage
    $preparedRequestInfo = json_decode($contentsGetUpdates, true, 512, JSON_THROW_ON_ERROR);
    foreach ($preparedRequestInfo['result'] as $item) {
        $responseText = 'Вы написали: [' . $item['message']['text'] . '].';
        $methodSendMessage = $client->get('sendMessage', [
            'query' => [
                'chat_id' => 266222035,
                'text' => $responseText
            ],
        ]);
    }
    //echo $methodGetUpdates->getBody();
    // sendMessage <<<

} catch (Throwable $e) {
    dd($e->getMessage());
}
