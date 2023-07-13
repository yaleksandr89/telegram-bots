<?php

namespace YaTranslationBot\Utils;

use Telegram\Bot\Keyboard\Keyboard;

trait DifferentTypesKeyboards
{
    // >>> SIMPLE KEYBOARDS
    protected static function simpleKeyboards(): array
    {
        return [
            ['Кнопка 1'],
            ['Кнопка 2'],
            ['Кнопка 3'],
        ];
    }

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
    protected static function getInlineKeyboardForTranslationBot(string $lang): array
    {
        if ('ru' === $lang) {
            $buttons = [
                [
                    'text' => 'Английский',
                    'callback_data' => 'en',
                ],
                [
                    'text' => '☑️ Русский',
                    'callback_data' => 'ru',
                ],
            ];
        } else {
            $buttons = [
                [
                    'text' => '☑️ English',
                    'callback_data' => 'en',
                ],
                [
                    'text' => 'Russian',
                    'callback_data' => 'ru',
                ],
            ];
        }

        return [$buttons];
    }
    // INLINE KEYBOARDS <<<

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
