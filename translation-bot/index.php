<?php

if (file_exists('../config.php')) {
    include_once '../config.php';
} else {
    die('Please, created config file.');
}

use Telegram\Bot\Api as TelegramBotApi;
use YaTranslationBot\Utils\TelegramBotApiHelper as Helper;

try {
    $telegram = new TelegramBotApi(YA_TRANSLATION_BOT);

    // >>> getUpdates
    $update = $telegram->getWebhookUpdate();
    Helper::writeToLogs($update->getRawResponse(), __DIR__ . '/update_logs.txt');
    // getUpdates <<<

    // >>> sendMessage
    if ($update->count() > 0) {
        $response = match (true) {
            array_key_exists('edited_message', $update->getRawResponse()) => Helper::definedTypeMessage($telegram, $update, 'edited_message'),
            array_key_exists('callback_query', $update->getRawResponse()) => Helper::definedTypeMessage($telegram, $update, 'callback_query'),
            default => Helper::definedTypeMessage($telegram, $update),
        };
    }
    // sendMessage <<<
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/try_catch_logs.txt', date('d.m.Y H:i:s') . PHP_EOL . print_r($e, true), FILE_APPEND);
}

die('Silence is golden');
