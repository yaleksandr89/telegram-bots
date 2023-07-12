<?php

namespace YaTranslationBot\Utils;

use DateTime;
use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Message as MessageObject;
use Telegram\Bot\Objects\Update;

class TelegramBotApiHelper
{
    use DifferentTypesKeyboards;

    /**
     * @throws TelegramSDKException
     *
     * // Из-за $telegram->answerCallbackQuery возвращаемый тип MessageObject|bool,
     * а не Telegram\Bot\Objects\Message ( ->answerCallbackQuery - возвращает bool)
     */
    public static function definedTypeMessage(
        TelegramBotApi $telegram,
        Update $update,
        bool $isEditMessage = false
    ): MessageObject|bool {
        if (isset($update['message'])) {
            $typeMessage = ($isEditMessage === false) ? $update['message'] : $update['edited_message'];
            $chatId = (int)$typeMessage['chat']['id'];
            $incomingText = isset($typeMessage['text']) ? strtolower(trim($typeMessage['text'])) : '';
        } else {
            $typeMessage = '';
            $incomingText = '';
            $chatId = -1;
        }

        if ('/start' === $incomingText) {
            $data = db()->getChatId($chatId);
            $lang = 'ru';
            $firstName = self::getFirstName($typeMessage);
            $lastName = self::getLastName($typeMessage);
            $username = self::getUsername($typeMessage);
            $date = self::getDate($typeMessage);

            if (null === $data) {
                db()->setChat(
                    $chatId,
                    $firstName,
                    $lastName,
                    $username,
                    $date,
                    $lang
                );
            } else {
                $lang = $data['lang'];
            }


            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: 'Оставьте отмеченный язык для перевода с него или выберите другой',
                additionalParams: [
                    'parse_mode' => 'Markdown',
                    'reply_markup' => self::preparedSelectedKeyboards(
                        keyBoards: self::inlineKeyboardsForStartCommand($lang),
                        inlineKeyboards: true
                    )
                ]
            );
        }

//        elseif ('Убрать клавиатуру' === $incomingText) {
//            $response = self::sendMessage(
//                telegram: $telegram,
//                chatId: $chatId,
//                message: 'Клавиатура убрана',
//                additionalParams: [
//                    'reply_markup' => self::removeSelectedKeyboard()
//                ]
//            );
//        }
//
//        elseif ('Открыть продвинутую клавиатуру' === $incomingText) {
//            $response = self::sendMessage(
//                telegram: $telegram,
//                chatId: $chatId,
//                message: 'Переключаюсь на продвинутую клавиатуру...',
//                additionalParams: [
//                    'parse_mode' => 'Markdown',
//                    'reply_markup' => self::preparedSelectedKeyboards(
//                        self::complexKeyboards(),
//                        [
//                            'resize_keyboard' => true,
//                            'one_time_keyboard' => true,
//                            'input_field_placeholder' => 'Выберите нужную команду'
//                        ]
//                    )
//                ]
//            );
//        }
//
//        elseif ('Вернуться на стартовую клавиатуру' === $incomingText) {
//            $response = self::sendMessage(
//                telegram: $telegram,
//                chatId: $chatId,
//                message: 'Возвращаюсь назад...',
//                additionalParams: [
//                    'parse_mode' => 'Markdown',
//                    'reply_markup' => self::preparedSelectedKeyboards(
//                        self::simpleKeyboardsWithComplexBtn(),
//                        [
//                            'resize_keyboard' => true,
//                            'one_time_keyboard' => true,
//                        ]
//                    )
//                ]
//            );
//        }

        return $response;
    }

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

    /**
     * @throws TelegramSDKException
     */
    private static function sendMessage(
        TelegramBotApi $telegram,
        int $chatId,
        string $message,
        array $additionalParams = [],
        int $delayMicroSecond = 0
    ): MessageObject {
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

    private static function getChatInfo(array $typeMessage): ?array
    {
        return $typeMessage['chat'] ?? null;
    }

    private static function getFirstName(array $typeMessage): string
    {
        $chat = self::getChatInfo($typeMessage);

        if (null === $chat) {
            return '';
        }

        return array_key_exists('first_name', $chat) ? $chat['first_name'] : '';
    }

    private static function getLastName(array $typeMessage): string
    {
        $chat = self::getChatInfo($typeMessage);

        if (null === $chat) {
            return '';
        }

        return array_key_exists('last_name', $chat) ? $chat['last_name'] : '';
    }

    private static function getUsername(array $typeMessage): string
    {
        $chat = array_key_exists('chat', $typeMessage) ? $typeMessage['chat'] : '';

        if ('' === $chat) {
            return '';
        }

        return array_key_exists('username', $chat) ? $chat['username'] : '';
    }

    private static function getDate(array $typeMessage): string
    {
        $date = new DateTime();
        $date->setTimestamp($typeMessage['date']);

        return $date->format('Y-m-d H:i:s');
    }
}
