<?php

use GuzzleHttp\Exception\GuzzleException;

if (file_exists('../config.php')) {
    include_once '../config.php';
} else {
    die('Please, created config file.');
}

$client = new GuzzleHttp\Client([
    'base_uri' => 'https://api.telegram.org/bot' . FIRST_BOT_TOKEN . '/'
]);

try {
    /*
    $methodGetMe = $client->get('getMe');
    $contentsGetMe = $methodGetMe->getBody()->getContents();
    //echo $contentsGetMe->getBody();
    dump(json_decode($contentsGetMe, true, 512, JSON_THROW_ON_ERROR));
    */

    $methodGetUpdates = $client->get('getUpdates', [
        'query' => [
            'offset' => 111342302,
            'limit' => 100 // 1 - min, 100 - max
        ],
    ]);
    $contentsGetUpdates = $methodGetUpdates->getBody()->getContents();
    //echo $methodGetUpdates->getBody();
    dump(json_decode($contentsGetUpdates, true, 512, JSON_THROW_ON_ERROR));

} catch (GuzzleException $e) {
    dd($e->getMessage());
} catch (JsonException $e) {
    dd($e->getMessage());
}