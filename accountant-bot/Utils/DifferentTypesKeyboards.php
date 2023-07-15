<?php

namespace AccountantBot\Utils;

use Telegram\Bot\Keyboard\Keyboard;

trait DifferentTypesKeyboards
{
    protected static function startKeyboard(): array
    {
        $nameMonth = getNameMonthByNumber(date('n'));
        $currentDate = date('d.m.Y');

        return [
            ['Справочная информация', 'Категории доходов', 'Категории расходов', 'Все категории',],
            ['Доходы за ' . $currentDate, 'Доходы за ' . $nameMonth,],
            ['Расходы за ' . $currentDate, 'Расходы за ' . $nameMonth,],
            ['Итого за ' . $currentDate, 'Итого за ' . $nameMonth,],
        ];
    }

    protected static function preparedSelectedKeyboards(
        array $keyBoards,
        array $additionalParams = [],
        bool $inlineKeyboards = false
    ): Keyboard {
        $typeKeyboard = $inlineKeyboards ? 'inline_keyboard' : 'keyboard';

        $params = [
            $typeKeyboard => $keyBoards,
        ];

        if (count($additionalParams) > 0) {
            $params = array_merge($params, $additionalParams);
        }

        return Keyboard::make($params);
    }

    protected static function removeSelectedKeyboard(): Keyboard
    {
        return Keyboard::make([
            'remove_keyboard' => true,
        ]);
    }
}
