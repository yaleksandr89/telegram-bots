<?php

namespace YaTranslationBot\Utils;

use DateTime;
use Dejurin\GoogleTranslateForFree;
use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Message;
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
        string $nameArrMessage = 'message'
    ): MessageObject|bool {
        switch ($nameArrMessage) {
            case 'message':
                $typeMessage = $update['message'];
                $chatId = (int)$typeMessage['chat']['id'];
                $incomingText = isset($typeMessage['text']) ? strtolower(trim($typeMessage['text'])) : '';
                break;
            case 'edited_message':
                $typeMessage = $update['edited_message'];
                $chatId = (int)$typeMessage['chat']['id'];
                $incomingText = isset($typeMessage['text']) ? strtolower(trim($typeMessage['text'])) : '';
                break;
            case 'callback_query':
                $typeMessage = $update['callback_query'];
                $chatId = (int)$typeMessage['message']['chat']['id'];
                $incomingText = '';
                break;
            default:
                $typeMessage = [];
                $chatId = -1;
                $incomingText = '';
                break;
        }

        if ('/start' === $incomingText) {
            $data = db()->getChatId($chatId);
            $lang = 'ru';
            $firstName = self::getFirstName($typeMessage);
            $lastName = self::getLastName($typeMessage);
            $username = self::getUsername($typeMessage);
            $date = self::getTimestampToDateTime($typeMessage);

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
                        keyBoards: self::getInlineKeyboardForTranslationBot($lang),
                        inlineKeyboards: true
                    )
                ]
            );
        } elseif ('callback_query' === $nameArrMessage) {
            $btnInlineKeyBoard = $typeMessage['message']['reply_markup']['inline_keyboard'][0];
            $now = new DateTime();
            $nowStr = $now->format('Y-m-d H:i:s');

            foreach ($btnInlineKeyBoard as $btn) {
                $isoCode = match ($btn['text']) {
                    'Русский', 'Russian' => 'ru',
                    'English', 'Английский' => 'en',
                    default => '',
                };

                if ($isoCode === $typeMessage['data']) {
                    db()->updateChat(
                        $chatId,
                        $typeMessage['data'],
                        $nowStr,
                    );

                    $telegram->answerCallbackQuery(['callback_query_id' => $typeMessage['id']]);

                    $response = self::sendMessage(
                        telegram: $telegram,
                        chatId: $chatId,
                        message: 'Можете вводить слово или фразу для перевода с выбранного языка',
                        additionalParams: [
                            'parse_mode' => 'Markdown',
                            'reply_markup' => self::preparedSelectedKeyboards(
                                keyBoards: self::getInlineKeyboardForTranslationBot($typeMessage['data']),
                                inlineKeyboards: true
                            )
                        ]
                    );

                    break;
                }
            }

            $telegram->answerCallbackQuery([
                'callback_query_id' => $typeMessage['id'],
                'text' => 'Это уже активный язык',
                'show_alert' => false, // Вызывает модальное окно, требуется клик/тап, что бы убрать
            ]);

            $response = true;
        } elseif ('' !== $incomingText) {
            $data = db()->getChatId($chatId);

            $source = ($data['lang'] === 'en') ? 'en' : 'ru';
            $target = ($data['lang'] === 'ru') ? 'en' : 'ru';
            $attempts = 5;

            $result = GoogleTranslateForFree::translate($source, $target, trim($incomingText), $attempts);

            self::writeToLogs(
                [
                    'chatId' => $data['chat_id'],
                    'full_name' => $data['first_name'] . ' ' . $data['last_name'],
                    'username' => $data['username'],
                    'textToTranslate' => $incomingText,
                    'translatedText' => $result,
                ],
                __DIR__ . '/../translations.txt'
            );

            if ($result) {
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: $result,
                );
            } else {
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: 'Сервис временно недоступен, попробуйте повторить позже',
                );
            }
        } else {
            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: 'Бот умеет работать только с текстом',
            );
        }

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

    private static function getTimestampToDateTime(array $typeMessage): string
    {
        $date = new DateTime();
        $date->setTimestamp($typeMessage['date']);

        return $date->format('Y-m-d H:i:s');
    }
}
