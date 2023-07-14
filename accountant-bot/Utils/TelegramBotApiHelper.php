<?php

namespace AccountantBot\Utils;

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
        [
            'typeMessage' => $typeMessage,
            'chatId' => $chatId,
            'incomingText' => $incomingText,
        ] = self::getDataForWork($update, $nameArrMessage);

        if (FROM_ID !== $typeMessage['from']['id']) {
            return self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: "В доступе отказано! 🚫\r\nЭто приватный бот."
            );
        }

        $nameMonth = getNameMonthByNumber(date('n'));
        $currentDate = date('d.m.Y');

        switch ($incomingText) {
            case '/start':
                $message = <<< MESSAGE
                Привет!
                Я бот помогающий следить за твоими финансами и домашней бухгалтерией.
                Если тебе потребуется справочная информация:
                 * Отправь команду <b>/help</b>
                 * Нажми соответствующую кнопку на клавиатуре 
                MESSAGE;

                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: $message,
                    additionalParams: [
                        'parse_mode' => 'HTML',
                        'reply_markup' => self::preparedSelectedKeyboards(
                            keyBoards: self::startKeyboard(),
                            additionalParams: [
                                'resize_keyboard' => true,
                            ]
                        )
                    ]
                );
                break;
            case '/help':
            case 'Справочная информация' === $incomingText:
                $message = <<< MESSAGE
                Для ведения учета просто добавьте свой доход или расход в следующем формате: <u>Тип: сумма - категория</u>
                
                <b>Примеры команд:</b>
                  <code>Доход: 1000 - Зарплата</code>
                  <code>Расход: 1000 - Коммунальные услуги</code>
                MESSAGE;

                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: $message,
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            case 'Все категории' === $incomingText:
                $allCategories = db()->getAllCategories();

                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: self::prepareCategoriesForResponse($allCategories),
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            case 'Категории доходов' === $incomingText:
                $incomeCategories = db()->getCategoriesForType(2);

                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: self::prepareCategoriesForResponse($incomeCategories),
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            case 'Категории расходов' === $incomingText:
                $expenseCategories = db()->getCategoriesForType(1);

                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: self::prepareCategoriesForResponse($expenseCategories),
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            case 1 === preg_match('/^Доход: ([\d.]+) - ([\w ]+)/iu', $incomingText, $matches):
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: self::setDataFinance($matches, 2),
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            case 1 === preg_match('/^Расход: ([\d.]+) - ([\w ]+)/iu', $incomingText, $matches):
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: self::setDataFinance($matches, 1),
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            case 'Доходы за ' . $currentDate === $incomingText:
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: self::getFinanceTodayWithCategory(2, 'Доходы за ' . $currentDate),
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            case 'Расходы за ' . $currentDate === $incomingText:
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: self::getFinanceTodayWithCategory(1, 'Расходы за сегодня'),
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            case 'Итого за ' . $currentDate === $incomingText:
                $dbIncomesToday = db()->getFinanceInfoForTodayWithoutCategories(2);
                $incomesToday = (null !== $dbIncomesToday) ? $dbIncomesToday['amount'] : 0;

                $dbExpensesToday = db()->getFinanceInfoForTodayWithoutCategories(1);
                $expensesToday = (null !== $dbExpensesToday) ? $dbExpensesToday['amount'] : 0;

                $resultToday = $incomesToday - $expensesToday;

                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: "<b>Итог за $currentDate</b>: $resultToday",
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            case 'Доходы за ' . $nameMonth === $incomingText:
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: self::getFinanceMonthWithCategory(2, 'Доходы за ' . $nameMonth),
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            case 'Расходы за ' . $nameMonth === $incomingText:
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: self::getFinanceMonthWithCategory(1, 'Расходы за ' . $nameMonth),
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            case 'Итого за ' . $nameMonth === $incomingText:
                $dbIncomesMonth = db()->getFinanceInfoForMonthWithoutCategories(2);
                $incomesMonth = (null !== $dbIncomesMonth) ? $dbIncomesMonth['amount'] : 0;

                $dbExpensesToday = db()->getFinanceInfoForMonthWithoutCategories(1);
                $expensesMonth = (null !== $dbExpensesToday) ? $dbExpensesToday['amount'] : 0;

                $resultMonth = $incomesMonth - $expensesMonth;

                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: "<b>Итог за $nameMonth</b>: $resultMonth",
                    additionalParams: [
                        'parse_mode' => 'HTML',
                    ]
                );
                break;
            default:
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: "Указан неверный формат для записи.\r\nОбратитесь к /help для ознакомления с примерами команд.",
                );
                break;
        }

        return $response;
    }

    public static function writeToLogs(array $update, string $pathToFile): void
    {
        if (count($update) > 0) {
            ob_start();
            echo match (true) {
                array_key_exists('message', $update) => '===[' . date('d-m-Y H:i:s', $update['message']['date']) . ']===' . PHP_EOL,
                array_key_exists('edited_message', $update) => '===[' . date('d-m-Y H:i:s', $update['edited_message']['edit_date']) . ']===' . PHP_EOL,
                array_key_exists('callback_query', $update) => '===[' . date('d-m-Y H:i:s', $update['callback_query']['message']['date']) . ']===' . PHP_EOL,
                default => '===[' . date('d-m-Y H:i:s', $update['date']) . ']===' . PHP_EOL,
            };

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

    private static function prepareCategoriesForResponse(array $categories): string
    {
        $txt = "<u>Список всех категорий</u>:\r\n";

        if (0 === count($categories)) {
            $txt = 'Категории не найдены';
        }

        foreach ($categories as $category) {
            $txt .= "    ⚬ {$category['title']}\r\n";
        }

        return $txt;
    }

    private static function setDataFinance(array $data, int $typeId): string
    {
        if (str_contains($data[1], '.')) {
            $amount = (float)$data[1];
        } else {
            $amount = (int)$data[1];
        }

        if (db()->isCategory($typeId, $data[2])) {
            if (db()->setFinance($typeId, $amount, $data[2],)) {
                $message = 'Запись успешно добавлена';
            } else {
                $message = 'При добавление записи произошла ошибка';
            }
        } else {
            $message = 'Категория не найдена';
        }

        return $message;
    }

    private static function getFinanceTodayWithCategory(int $typeId, string $header): string
    {
        $financeTodayWithCategoryData = db()->getFinanceInfoForTodayWithCategories($typeId);
        $categories = array_column($financeTodayWithCategoryData, 'category');
        $amounts = array_column($financeTodayWithCategoryData, 'amount');

        $result = array_combine($categories, $amounts);

        $message = "<u>$header</u>\r\n";
        $total = 0;
        foreach ($result as $categoryName => $amountByCategory) {
            $message .= "  * $categoryName - $amountByCategory\r\n";
            $total += $amountByCategory;
        }
        if (0 !== $total) {
            $message .= "\r\n<b>Всего: $total</b>";
        } else {
            $message .= "<b>отсутствуют</b>";
        }

        return $message;
    }

    private static function getFinanceMonthWithCategory(int $typeId, string $header): string
    {
        $financeMonthWithCategoryData = db()->getFinanceInfoForMonthWithCategories($typeId);
        $categories = array_column($financeMonthWithCategoryData, 'category');
        $amounts = array_column($financeMonthWithCategoryData, 'amount');

        $result = array_combine($categories, $amounts);

        $message = "<u>$header</u>\r\n";
        $total = 0;
        foreach ($result as $categoryName => $amountByCategory) {
            $message .= "  * $categoryName - $amountByCategory\r\n";
            $total += $amountByCategory;
        }
        if (0 !== $total) {
            $message .= "\r\n<b>Всего: $total</b>";
        }

        return $message;
    }
}
