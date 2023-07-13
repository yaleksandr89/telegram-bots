<?php

namespace YaTranslationBotV2\Utils;

use DateTime;
use Dejurin\GoogleTranslateForFree;
use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Message as MessageObject;
use Telegram\Bot\Objects\Update;

class TelegramBotApiHelper
{
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
        [
            'typeMessage' => $typeMessage,
            'chatId' => $chatId,
            'incomingText' => $incomingText
        ] = self::getDataForWork($update, $nameArrMessage);

        if ('/start' === $incomingText) {
            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: "Привет!\r\nЯ Помогу перевести слово или предложение с русского на английский и обратно.\r\n\r\nДля начала работы просто введи фразу и отправьте мне.",
            );
        } elseif ('' !== $incomingText) {
            $attempts = 5;

            if (preg_match('/[a-z]+/ui', $incomingText)) {
                $source = 'en';
                $target = 'ru';
                $sourceLang = 'Русский: ';
            } else {
                $source = 'ru';
                $target = 'en';
                $sourceLang = 'English: ';
            }

            $result = GoogleTranslateForFree::translate($source, $target, trim($incomingText), $attempts);

            self::writeToLogs(
                [
                    'chatId' => $chatId,
                    'textToTranslate' => $incomingText,
                    'translatedText' => $result,
                ],
                __DIR__ . '/../translations.txt'
            );

            if ($result) {
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: $sourceLang . $result,
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

    private static function getDataForWork(Update $update, string $nameArrMessage): array
    {
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

        return [
            'typeMessage' => $typeMessage,
            'chatId' => $chatId,
            'incomingText' => $incomingText,
        ];
    }
}
