<?php

namespace TbaPhpSdk\Utils;

use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;

class TelegramBotApiHelper
{
    /**
     * @var string
     */
    public static string $pathToImages = '';

    /**
     * @var string
     */
    public static string $pathToDocs = '';

    /**
     * @return array
     */
    public static function getRandImg(): array
    {
        $pathToImage = ('' !== self::$pathToImages) ? self::$pathToImages : __DIR__;

        $listImg = scandir($pathToImage);
        return array_diff($listImg, ['.', '..']);
    }

    /**
     * @return array
     */
    public static function getRandDocs(): array
    {
        $pathToDoc = ('' !== self::$pathToDocs) ? self::$pathToDocs : __DIR__;

        $listDoc = scandir($pathToDoc);
        return array_diff($listDoc, ['.', '..']);
    }

    /**
     * @param TelegramBotApi $telegram
     * @param Update $update
     * @param bool $isEditMessage
     * @return Message
     * @throws TelegramSDKException
     */
    public static function definedTypeMessage(TelegramBotApi $telegram, Update $update, bool $isEditMessage = false): Message
    {
        $arrImg = self::getRandImg();
        $randImg = $arrImg[array_rand($arrImg)];

        $arrDocs = self::getRandDocs();
        $randDocs = $arrDocs[array_rand($arrDocs)];

        $typeMessage = ($isEditMessage === false) ? $update['message'] : $update['edited_message'];

        $chatId = $typeMessage['chat']['id'];
        $photoCommand = strtolower(trim($typeMessage['text']));

        switch ($photoCommand) {
            case '/photo':
                $telegram->sendMessage([
                    'chat_id' => (string)$chatId,
                    'text' => 'Подбираю изображение...',
                ]);
                usleep(500000);
                $telegram->sendMessage([
                    'chat_id' => (string)$chatId,
                    'text' => 'Отправляю изображение...',
                ]);
                usleep(500000);
                $response = $telegram->sendPhoto([
                    'chat_id' => (string)$chatId,
                    'photo' => InputFile::create(self::$pathToImages . $randImg),
                    'caption' => $randImg
                ]);
                break;
            case '/document':
                $telegram->sendMessage([
                    'chat_id' => (string)$chatId,
                    'text' => 'Отправляю файл...',
                ]);
                usleep(500000);
                $response = $telegram->sendDocument([
                    'chat_id' => (string)$chatId,
                    'document' => InputFile::create(self::$pathToDocs . $randDocs),
                    'caption' => $randDocs
                ]);
                break;
            case '/start':
                $telegram->sendMessage([
                    'chat_id' => (string)$chatId,
                    'text' => 'Отправляю файл...',
                ]);
                $response = $telegram->sendMessage([
                    'chat_id' => (string)$chatId,
                    'text' => 'Вы активировали команду `start`',
                    'parse_mode' => 'Markdown' // OR 'HTML'
                ]);
                break;
            case '/help':
                $response = $telegram->sendMessage([
                    'chat_id' => (string)$chatId,
                    'text' => 'Появились вопросы?' . PHP_EOL . '[Свяжитесь со мной](https://yaleksandr89.github.io/)',
                    'parse_mode' => 'Markdown', // OR 'HTML'
                    //'disable_web_page_preview' => true
                ]);
                break;
            default:
                $textResponse = ($isEditMessage === false)
                    ? "*Привет {$typeMessage['from']['first_name']}*. Ты написал: '{$typeMessage['text']}'"
                    : '(Сообщение отредактировано в ' . date('d.m.Y H:i:s', $typeMessage['edit_date']) . ')' . PHP_EOL . "*Привет {$typeMessage['from']['first_name']}*" . PHP_EOL . "Тобой было написано: '{$typeMessage['text']}'";

                $response = $telegram->sendMessage([
                    'chat_id' => (string)$chatId,
                    'text' => $textResponse,
                    'parse_mode' => 'Markdown' // OR 'HTML'
                ]);
        }

        return $response;
    }

    /**
     * @param array $update
     * @param string $pathToFile
     * @return void
     */
    public static function writeToLogs(array $update, string $pathToFile): void
    {
        if (count($update) > 0) {
            ob_start();
            if (array_key_exists('message', $update)) {
                echo '===[' . date('d-m-Y H:i:s', $update['message']['date']) . ']===' . PHP_EOL;
            } elseif (array_key_exists('edited_message', $update)) {
                echo '===[' . date('d-m-Y H:i:s', $update['edited_message']['edit_date']) . ']===' . PHP_EOL;
            } else {
                echo '===[' . date('d-m-Y H:i:s', $update['date']) . ']===' . PHP_EOL;
            }

            print_r($update);
            echo '------------------' . PHP_EOL;
            $log = ob_get_clean();

            if ('' !== $log) {
                file_put_contents($pathToFile, $log, FILE_APPEND);
            }
        }
    }
}
