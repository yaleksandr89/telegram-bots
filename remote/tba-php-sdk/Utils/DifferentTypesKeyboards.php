<?php

namespace TbaPhpSdk\Utils;

use Telegram\Bot\Keyboard\Keyboard;

trait DifferentTypesKeyboards
{
    // >>> SIMPLE KEYBOARDS
    /**
     * @return array
     */
    protected static function simpleKeyboards(): array
    {
        return [
            ['Кнопка 1'],
            ['Кнопка 2'],
            ['Кнопка 3'],
        ];
    }

    /**
     * @return array
     */
    protected static function simpleKeyboardsWithComplexBtn(): array
    {
        return [
            [
                [
                    'text' => 'Отправить контакт',
                    'request_contact' => true,
                ],
                [
                    'text' => 'Отправить локацию',
                    'request_location' => true,
                ]
            ],
            ['Открыть продвинутую клавиатуру'],
            ['Убрать клавиатуру'],
        ];
    }

    /**
     * @return array
     */
    protected static function complexKeyboards(): array
    {
        return [
            ['Кнопка 1', 'Кнопка 2', 'Кнопка 3'],
            ['Кнопка 4', 'Кнопка 5'],
            ['Вернуться на стартовую клавиатуру'],
            ['Убрать клавиатуру'],
        ];
    }
    // SIMPLE KEYBOARDS <<<

    // >>> INLINE KEYBOARDS
    /**
     * @return array
     */
    protected static function inlineKeyboards(): array
    {
        return [
            [
                [
                    'text' => 'Сайт визитка',
                    'url' => 'https://yaleksandr89.github.io/',
                ],
                [
                    'text' => 'Вконтакте',
                    'url' => 'https://vk.com/y.aleksandr89',
                ],
            ],
            [
                [
                    'text' => 'Функциональная кнопка',
                    'callback_data' => 'cb_query',
                ],
            ],
        ];
    }
    // >>> INLINE KEYBOARDS

    /**
     * @param array $keyBoards
     * @param array $additionalParams
     * @param bool $inlineKeyboards
     * @return Keyboard
     */
    protected static function preparedSelectedKeyboards(array $keyBoards, array $additionalParams = [], bool $inlineKeyboards = false): Keyboard
    {
        $typeKeyboard = $inlineKeyboards ? 'inline_keyboard' : 'keyboard';

        $params = [
            $typeKeyboard => $keyBoards,
        ];

        if (count($additionalParams) > 0) {
            $params = array_merge($params, $additionalParams);
        }

        return Keyboard::make($params);
    }

    /**
     * @return Keyboard
     */
    protected static function removeSelectedKeyboard(): Keyboard
    {
        return Keyboard::make([
            'remove_keyboard' => true,
        ]);
    }
}
