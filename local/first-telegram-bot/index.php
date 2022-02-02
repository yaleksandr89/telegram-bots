<?php

use FirstBot\EchoTelegramBot;
use GuzzleHttp\Client;

if (file_exists('../../config.php')) {
    include_once '../../config.php';
} else {
    die('Please, created config file.');
}

try {
    $echoTelegramBot = new EchoTelegramBot(new Client(), FIRST_BOT_TOKEN, BASE_URL);

    /** DON'T USE AN INFINITE LOOP ON A REAL SERVER! */
    while (true) {
        // >>> getUpdates
        $getUpdatesContent = $echoTelegramBot->getUpdates(true);
        // getUpdates <<<

        // >>> sendMessage
        if (count($getUpdatesContent) > 0) {
            foreach ($getUpdatesContent as $item) {
                $responseText = '';
                $chatId = 0;

                if (array_key_exists('message', $item)) {
                    $responseText = "Привет {$item['message']['from']['first_name']}. Ты написал: '{$item['message']['text']}'";
                    $chatId = $item['message']['chat']['id'];
                }

                if (array_key_exists('edited_message', $item)) {
                    $responseText = '(Сообщение было отредактировано)' .
                        PHP_EOL .
                        "Привет {$item['edited_message']['from']['first_name']}. Ты написал: '{$item['edited_message']['text']}'";
                    $chatId = $item['edited_message']['chat']['id'];
                }

                $echoTelegramBot->sendMessage($chatId, $responseText);
            }
        }
        // sendMessage <<<

        sleep(2);
    }
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/try_catch_logs.txt', date('d.m.Y H:i:s') . PHP_EOL . print_r($e, true), FILE_APPEND);
}

die('Silence is gold.');