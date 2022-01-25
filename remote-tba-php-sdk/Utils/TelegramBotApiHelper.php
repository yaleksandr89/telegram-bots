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
     * @var string
     */
    public static string $pathToVideos = '';

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
        $pathToDocs = ('' !== self::$pathToDocs) ? self::$pathToDocs : __DIR__;

        $listDoc = scandir($pathToDocs);
        return array_diff($listDoc, ['.', '..']);
    }

    /**
     * @return array
     */
    public static function getRandVideos(): array
    {
        $pathToVideos = ('' !== self::$pathToVideos) ? self::$pathToVideos : __DIR__;

        $listVideo = scandir($pathToVideos);
        return array_diff($listVideo, ['.', '..']);
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

        $arrVideos = self::getRandVideos();
        $randVideo = $arrVideos[array_rand($arrVideos)];

        $typeMessage = ($isEditMessage === false) ? $update['message'] : $update['edited_message'];

        $chatId = $typeMessage['chat']['id'];
        $incomingText = strtolower(trim($typeMessage['text']));

        // Поиск сообщения с содержанием координат, для отправки карты с меткой
        preg_match('/^(location:)(.+)$/i', $incomingText, $matchesCoordinates);

        if ('/photo' === $incomingText) {
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
        } elseif ('/document' === $incomingText) {
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
        } elseif ('/video' === $incomingText) {
            $response = $telegram->sendVideo([
                'chat_id' => (string)$chatId,
                'video' => InputFile::create(self::$pathToVideos . $randVideo),
                'caption' => $randVideo
            ]);
        } elseif ('/sticker' === $incomingText) {
            $arrStickers = [
                'CAACAgIAAxkBAAIBuGHwdjKPYNPl15xsJpJVGsoKjDOVAAIUDwAC1I9gS7jbkdj0UX0_IwQ',
                'CAACAgIAAxkBAAIBumHwdjUeAAFgSOUos4_vqFxO54pCEwACaAEAAj0N6ATymcINj4C7YyME',
                'CAACAgIAAxkBAAIBvGHwdjmIWqn4oORTUbpQrMt3d2vGAAJJAQACe04qENKK0NXppX3fIwQ',
                'CAACAgIAAxkBAAIBvmHwdkCfDsFr75EXtNnbAfHeHq49AAJ8AQACe04qENf3ZOpShYC8IwQ',
                'CAACAgIAAxkBAAIBwGHwdkX-LO3oSG0DXi0i2LihHkrXAAJGAANSiZEj-P7l5ArVCh0jBA',
                'CAACAgIAAxkBAAIBwmHwdkhabKx3yNFnt_VoaJJtUi4NAAJcAQACPQ3oBAABMsv78bItBCME',
            ];

            $response = $telegram->sendSticker([
                'chat_id' => (string)$chatId,
                'sticker' => $arrStickers[array_rand($arrStickers)],
            ]);
        } elseif ('/start' === $incomingText) {
            $response = $telegram->sendMessage([
                'chat_id' => (string)$chatId,
                'text' => 'Вы активировали команду `start`',
                'parse_mode' => 'Markdown' // OR 'HTML'
            ]);
        } elseif ('/help' === $incomingText) {
            $response = $telegram->sendMessage([
                'chat_id' => (string)$chatId,
                'text' => 'Появились вопросы?' . PHP_EOL . '[Свяжитесь со мной](https://yaleksandr89.github.io/)',
                'parse_mode' => 'Markdown', // OR 'HTML'
                //'disable_web_page_preview' => true
            ]);
        } elseif (array_key_exists('sticker', $typeMessage)) {
            $idSticker = $typeMessage['sticker']['file_id'];
            $response = $telegram->sendMessage([
                'chat_id' => (string)$chatId,
                'text' => "Вы отправили стикер.\nИдентификатор стикера: `$idSticker`",
                'parse_mode' => 'Markdown'
            ]);
        } elseif (count($matchesCoordinates) > 2) {
            $coordinates = preg_replace('/\s/', '', $matchesCoordinates[2]);
            $coordinates = explode(',', $coordinates);

            if (isset($coordinates[0], $coordinates[1]) && (is_numeric($coordinates[0]) && is_numeric($coordinates[1]))) {
                $response = $telegram->sendLocation([
                    'chat_id' => (string)$chatId,
                    'latitude' => $coordinates[0],
                    'longitude' => $coordinates[1],
                ]);
            } else {
                $telegram->sendMessage([
                    'chat_id' => (string)$chatId,
                    'text' => 'Переданные координаты некорректны!',
                ]);
                die;
            }
        } else {
            $textResponse = ($isEditMessage === false)
                ? "*Привет {$typeMessage['from']['first_name']}*. Ты написал: '{$typeMessage['text']}'"
                : '(Сообщение отредактировано в ' . date('d.m.Y H:i:s', $typeMessage['edit_date']) . ')' . PHP_EOL . "*Привет {$typeMessage['from']['first_name']}*" . PHP_EOL . "Тобой было написано: '{$typeMessage['text']}'";

            $response = $telegram->sendMessage([
                'chat_id' => (string)$chatId,
                'text' => $textResponse,
                'parse_mode' => 'Markdown'
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
