<?php

namespace TbaPhpSdk\Utils;

use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\Message as MessageObject;

class TelegramBotApiHelper
{
    use DifferentTypesKeyboards;

    /** @var array $arrIdStickers */
    public static array $arrIdStickers = [
        'CAACAgIAAxkBAAIBuGHwdjKPYNPl15xsJpJVGsoKjDOVAAIUDwAC1I9gS7jbkdj0UX0_IwQ',
        'CAACAgIAAxkBAAIBumHwdjUeAAFgSOUos4_vqFxO54pCEwACaAEAAj0N6ATymcINj4C7YyME',
        'CAACAgIAAxkBAAIBvGHwdjmIWqn4oORTUbpQrMt3d2vGAAJJAQACe04qENKK0NXppX3fIwQ',
        'CAACAgIAAxkBAAIBvmHwdkCfDsFr75EXtNnbAfHeHq49AAJ8AQACe04qENf3ZOpShYC8IwQ',
        'CAACAgIAAxkBAAIBwGHwdkX-LO3oSG0DXi0i2LihHkrXAAJGAANSiZEj-P7l5ArVCh0jBA',
        'CAACAgIAAxkBAAIBwmHwdkhabKx3yNFnt_VoaJJtUi4NAAJcAQACPQ3oBAABMsv78bItBCME',
    ];

    /**
     * @param TelegramBotApi $telegram
     * @param Update $update
     * @param bool $isEditMessage
     * @return MessageObject|bool
     * @throws TelegramSDKException
     *
     * // Из-за $telegram->answerCallbackQuery возвращаемый тип MessageObject|bool,
     * а не Telegram\Bot\Objects\Message ( ->answerCallbackQuery - возвращает bool)
     */
    public static function definedTypeMessage(TelegramBotApi $telegram, Update $update, bool $isEditMessage = false): MessageObject|bool
    {
        // Получение случайного файла из 'img'
        $imgFolder = __DIR__ . '/../img/';
        $arrImg = self::getRandFile($imgFolder);
        $randImg = $arrImg[array_rand($arrImg)];

        // Получение случайного файла из 'docs'
        $docsFolder = __DIR__ . '/../docs/';
        $arrDocs = self::getRandFile($docsFolder);
        $randDocs = $arrDocs[array_rand($arrDocs)];

        // Получение случайного файла из 'videos'
        $videosFolder = __DIR__ . '/../videos/';
        $arrVideos = self::getRandFile($videosFolder);
        $randVideo = $arrVideos[array_rand($arrVideos)];

        if (isset($update['message'])) {
            $typeMessage = ($isEditMessage === false) ? $update['message'] : $update['edited_message'];
            $chatId = (string)$typeMessage['chat']['id'];
            $incomingText = isset($typeMessage['text']) ? strtolower(trim($typeMessage['text'])) : '';
        } else {
            $typeMessage = '';
            $incomingText = '';
            $chatId = -1;
        }

        preg_match('/^(location:)(.+)$/i', $incomingText, $matchesCoordinates); // Поиск сообщения с содержанием координат, для отправки карты с меткой
        usleep(250000); // Задержка выполнения между командами

        if ('/photo' === $incomingText) {
            self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: 'Подбираю изображение...',
                delayMicroSecond: 500000
            );
            self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: 'Отправляю изображение...',
                delayMicroSecond: 500000
            );

            $response = $telegram->sendPhoto([
                'chat_id' => $chatId,
                'photo' => InputFile::create($imgFolder . $randImg),
                'caption' => $randImg
            ]);
        } elseif ('/document' === $incomingText) {
            self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: 'Отправляю файл...',
                delayMicroSecond: 500000
            );

            $response = $telegram->sendDocument([
                'chat_id' => $chatId,
                'document' => InputFile::create($docsFolder . $randDocs),
                'caption' => $randDocs
            ]);
        } elseif ('/video' === $incomingText) {
            $response = $telegram->sendVideo([
                'chat_id' => $chatId,
                'video' => InputFile::create($videosFolder . $randVideo),
                'caption' => $randVideo
            ]);
        } elseif ('/sticker' === $incomingText) {
            $response = $telegram->sendSticker([
                'chat_id' => $chatId,
                'sticker' => self::$arrIdStickers[array_rand(self::$arrIdStickers)],
            ]);
        } elseif ('/start' === $incomingText) {
            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: 'Выберите команду',
                additionalParams: [
                    'parse_mode' => 'Markdown',
                    'reply_markup' => self::preparedSelectedKeyboards(
                        self::simpleKeyboardsWithComplexBtn(),
                        [
                            'resize_keyboard' => true,
                            'one_time_keyboard' => true,
                            'input_field_placeholder' => 'Выберите нужную команду'
                        ]
                    )
                ]
            );
        } elseif ('Убрать клавиатуру' === $incomingText) {
            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: 'Клавиатура убрана',
                additionalParams: [
                    'reply_markup' => self::removeSelectedKeyboard()
                ]
            );
        } elseif ('Открыть продвинутую клавиатуру' === $incomingText) {
            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: 'Переключаюсь на продвинутую клавиатуру...',
                additionalParams: [
                    'parse_mode' => 'Markdown',
                    'reply_markup' => self::preparedSelectedKeyboards(
                        self::complexKeyboards(),
                        [
                            'resize_keyboard' => true,
                            'one_time_keyboard' => true,
                            'input_field_placeholder' => 'Выберите нужную команду'
                        ]
                    )
                ]
            );
        } elseif ('Вернуться на стартовую клавиатуру' === $incomingText) {
            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: 'Возвращаюсь назад...',
                additionalParams: [
                    'parse_mode' => 'Markdown',
                    'reply_markup' => self::preparedSelectedKeyboards(
                        self::simpleKeyboardsWithComplexBtn(),
                        [
                            'resize_keyboard' => true,
                            'one_time_keyboard' => true,
                        ]
                    )
                ]
            );
        } elseif ('/help' === $incomingText) {
            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: 'Выберите команду',
                additionalParams: [
                    //'parse_mode' => 'Markdown',
                    'reply_markup' => self::preparedSelectedKeyboards(
                        keyBoards: self::inlineKeyboards(),
                        inlineKeyboards: true
                    )
                ]
            );
        } elseif (isset($typeMessage['sticker'])) {
            $idSticker = $typeMessage['sticker']['file_id'];
            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: "Вы отправили стикер.\nИдентификатор стикера: `$idSticker`",
                additionalParams: [
                    'parse_mode' => 'Markdown'
                ]
            );
        } elseif (count($matchesCoordinates) > 2) {
            $coordinates = preg_replace('/\s/', '', $matchesCoordinates[2]);
            $coordinates = explode(',', $coordinates);

            if (isset($coordinates[0], $coordinates[1]) && (is_numeric($coordinates[0]) && is_numeric($coordinates[1]))) {
                $response = $telegram->sendLocation([
                    'chat_id' => $chatId,
                    'latitude' => $coordinates[0],
                    'longitude' => $coordinates[1],
                ]);
            } else {
                self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: 'Переданные координаты некорректны!'
                );
                die;
            }
        } elseif (isset($update['callback_query'])) {
            $telegram->answerCallbackQuery([
                'callback_query_id' => $update['callback_query']['id'],
                'text' => 'Сработала функция обратного вызова ' . $update['callback_query']['data'],
                'show_alert' => false,
            ]);

            $response = $telegram->editMessageText([
                'chat_id' => $update['callback_query']['message']['chat']['id'],
                'message_id' => $update['callback_query']['message']['message_id'],
                'text' => 'Команда обработана в '. date('d.m.Y H:i:s') .' 👍',
                'reply_markup' => self::preparedSelectedKeyboards(
                    keyBoards: self::inlineKeyboards(),
                    inlineKeyboards: true
                )
            ]);
        } else {
            if (array_key_exists('location', $typeMessage) || array_key_exists('contact', $typeMessage)) {
                die;
            }

            $textResponse = ($isEditMessage === false)
                ? "*Привет {$typeMessage['from']['first_name']}*. Ты написал: '{$typeMessage['text']}'"
                : '(Сообщение отредактировано в ' . date('d.m.Y H:i:s', $typeMessage['edit_date']) . ')' . PHP_EOL . "*Привет {$typeMessage['from']['first_name']}*" . PHP_EOL . "Тобой было написано: '{$typeMessage['text']}'";

            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: $textResponse,
                additionalParams: [
                    'parse_mode' => 'Markdown'
                ]
            );
        }

        return $response;
    }

    /**
     * @param TelegramBotApi $telegram
     * @param string $chatId
     * @param string $message
     * @param array $additionalParams
     * @param int $delayMicroSecond
     * @return MessageObject
     * @throws TelegramSDKException
     */
    private static function sendMessage(TelegramBotApi $telegram, string $chatId, string $message, array $additionalParams = [], int $delayMicroSecond = 0): MessageObject
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $message,
        ];

        if (count($additionalParams) > 0) {
            $params = array_merge($params, $additionalParams);
        }

        if (0 !== $delayMicroSecond) {
            usleep($delayMicroSecond);
            return $telegram->sendMessage($params);
        }

        return $telegram->sendMessage($params);
    }

    /**
     * @param string $pathToFolder
     * @return array
     */
    private static function getRandFile(string $pathToFolder): array
    {
        $pathToFiles = ('' !== $pathToFolder) ? $pathToFolder : __DIR__;
        $listFile = scandir($pathToFiles);

        return array_diff($listFile, ['.', '..']);
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
            } elseif (array_key_exists('callback_query', $update)) {
                echo '===[' . date('d-m-Y H:i:s', $update['callback_query']['message']['date']) . ']===' . PHP_EOL;
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
