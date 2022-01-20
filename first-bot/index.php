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
    $response = $client->get('getMe');
    $contents = $response->getBody()->getContents();
    dump(json_decode($contents, true, 512, JSON_THROW_ON_ERROR));
} catch (GuzzleException $e) {
    dd($e->getMessage());
} catch (JsonException $e) {
    dd($e->getMessage());
}