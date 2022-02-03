<?php

use SecondBot\EchoTelegramBot as SecondEchoTelegramBot;
use GuzzleHttp\Client;

if (file_exists('../../config.php')) {
    include_once '../../config.php';
} else {
    die('Please, created config file.');
}

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

        echo $typeMessage['text'] . PHP_EOL;

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

    $echoTelegramBot = new SecondEchoTelegramBot(new Client(), FIRST_BOT_TOKEN, BASE_URL);

    /** DON'T USE AN INFINITE LOOP ON A REAL SERVER! */
    while (true) {
        // >>> getUpdates
        $getUpdatesContent = $echoTelegramBot->getUpdates(true);
        // getUpdates <<<

        // >>> sendMessage
        if (count($getUpdatesContent) > 0) {
            foreach ($getUpdatesContent as $item) {
                if (array_key_exists('message', $item)) {
                    definedTypeMessage($echoTelegramBot, $item);
                }

                if (array_key_exists('edited_message', $item)) {
                    definedTypeMessage($echoTelegramBot, $item, true);
                }
            }
        }
        // sendMessage <<<

        sleep(2);
    }
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/try_catch_logs.txt', date('d.m.Y H:i:s') . PHP_EOL . print_r($e, true), FILE_APPEND);
}

die('Silence is gold.');