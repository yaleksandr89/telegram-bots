<?php

if (file_exists('../../config.php')) {
    include_once '../../config.php';
} else {
    die('Please, created config file.');
}

use GuzzleHttp\Client;
use SimpleBot\EchoTelegramBot;

/** @var GuzzleHttp\Client $client */

/** WORKS ONLY ON HOSTING OR VDS */
try {
    function getRandImg(): array
    {
        $listImg = scandir(__DIR__ . '/img/');
        return array_diff($listImg, ['.', '..']);
    }

    function definedTypeMessage(object $telegramBot, array $item, bool $isEditMessage = false)
    {
        $arrImg = getRandImg();
        $randImg = $arrImg[array_rand($arrImg)];

        $typeMessage = ($isEditMessage === false) ? $item['message'] : $item['edited_message'];

        $chatId = $typeMessage['chat']['id'];
        $photoCommand = strtolower(trim($typeMessage['text']));

        if ('photo' === $photoCommand) {
            $telegramBot->sendPhoto(
                $chatId,
                __DIR__ . '/img/' . $randImg,
                [
                    'caption' => 'PicSum photos'
                ]
            );
        } else {
            $textResponse = ($isEditMessage === false)
                ? "*Привет {$typeMessage['from']['first_name']}*. Ты написал: '{$typeMessage['text']}'"
                : '(СООБЩЕНИЕ ОТРЕДАКТИРОВАННО)' . PHP_EOL . "*Привет {$typeMessage['from']['first_name']}*. Ты написал: '{$typeMessage['text']}'";

            $telegramBot->sendMessage(
                $chatId,
                $textResponse,
                [
                    'parse_mode' => 'Markdown' // OR 'HTML'
                ]
            );
        }
    }

    $echoTelegramBot = new EchoTelegramBot(new Client(), SIMPLE_BOT_TOKEN, BASE_URL);

    // >>> getUpdates
    $update = json_decode(
        file_get_contents('php://input'),
        true
    );
    // getUpdates <<<

    // >>> sendMessage
    if (isset($update)) {
        $echoTelegramBot->addToLogs($update);

        if (array_key_exists('message', $update)) {
            definedTypeMessage($echoTelegramBot, $update);
        }

        if (array_key_exists('edited_message', $update)) {
            definedTypeMessage($echoTelegramBot, $update, true);
        }
    }
    // sendMessage <<<
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/try_catch_logs.txt', date('d.m.Y H:i:s') . PHP_EOL . print_r($e, true), FILE_APPEND);
}

die('Silence is gold.');
