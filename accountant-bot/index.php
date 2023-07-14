<?php

if (file_exists('../config.php')) {
    include_once '../config.php';
} else {
    die('Please, created config file.');
}

use AccountantBot\Utils\DB;
use AccountantBot\Utils\TelegramBotApiHelper as Helper;
use Telegram\Bot\Api as TelegramBotApi;

try {
    function getNameMonthByNumber(int $numberMonth): string
    {
        $months = [
            'январь',
            'февраль',
            'март',
            'апрель',
            'май',
            'июнь',
            'июль',
            'август',
            'сентябрь',
            'октябрь',
            'ноябрь',
            'декабрь'
        ];

        return $months[$numberMonth - 1];
    }

    function db(): DB
    {
        return DB::getInstance()->getConnection(PARAMS_DB_ACCOUNTANT);
    }

    $telegram = new TelegramBotApi(YA_ACCOUNTANT_BOT);

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
