<?php

namespace WeatherBot\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Telegram\Bot\Api as TelegramBotApi;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Message as MessageObject;
use Telegram\Bot\Objects\Update;

class TelegramBotApiHelper
{
    /**
     * @throws TelegramSDKException
     * @throws GuzzleException
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
        $fromLang = $typeMessage['from']['language_code'];

        if ('/start' === $incomingText) {
            $message = <<< MESSAGE
            Привет!
            Я бот синоптик. Если вам нужен прогноз погоды или другая информация о погодных условиях, просто спросите меня.
            Получить погоду можно следующими способами:
            1. Отправить геолокацию (<b>доступно только с мобильных устройств</b>).
            2. Указать название города в форте: <b>Город</b> или <b>Город, код страны</b> (Пример: <code>Москва</code> или <code>Москва,ru</code>)
            MESSAGE;
            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: $message,
                additionalParams: [
                    'parse_mode' => 'HTML'
                ]
            );
        } elseif ('' !== $incomingText) {
            $result = self::getForecastByNameCityOrNameCityAndCountryCode(
                'GET',
                $incomingText,
                $fromLang
            );

            if ('error' === $result['status']) {
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: $result['text'],
                    additionalParams: [
                        'parse_mode' => 'HTML'
                    ]
                );
            } else {
                $response = $telegram->sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => InputFile::create('https://openweathermap.org/img/wn/' . $result['icon'] . '@4x.png'),
                    'caption' => $result['text'],
                    'parse_mode' => 'HTML',
                ]);
            }
        } elseif (array_key_exists('location', $typeMessage)) {
            $result = self::getForecastByLocation(
                'GET',
                $typeMessage['location'],
                $fromLang
            );

            if ('error' === $result['status']) {
                $response = self::sendMessage(
                    telegram: $telegram,
                    chatId: $chatId,
                    message: $result['text'],
                    additionalParams: [
                        'parse_mode' => 'HTML'
                    ]
                );
            } else {
                $response = $telegram->sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => InputFile::create('https://openweathermap.org/img/wn/' . $result['icon'] . '@4x.png'),
                    'caption' => $result['text'],
                    'parse_mode' => 'HTML',
                ]);
            }
        } else {
            $response = self::sendMessage(
                telegram: $telegram,
                chatId: $chatId,
                message: 'Что-то пошло не так -_-',
            );
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
                array_key_exists('open_weather', $update) => '===[' . date('d-m-Y H:i:s') . ']===' . PHP_EOL,
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

    /**
     * @throws GuzzleException
     */
    private static function getForecastByNameCityOrNameCityAndCountryCode(
        string $method,
        string $city,
        string $fromLang,
    ): array {
        if (str_contains($city, ',')) {
            $cityWithCode = explode(',', $city);
            [$nameCity, $code] = $cityWithCode;
            $nameCity .= ',' . $code;
        } else {
            $nameCity = $city;
            $code = $fromLang;
        }

        $responseFromOpenWeather = self::getResponseFromOpenWeather($method, [
            'query' => [
                'appid' => OPEN_WEATHER_MAP_TOKEN,
                'q' => trim($nameCity),
                'units' => ('ru' === $code) ? 'metric' : 'imperial',
                'lang' => trim($code),
            ],
        ]);

        switch ($responseFromOpenWeather['status']) {
            case 401:
                self::writeToLogs(
                    [
                        'open_weather' => [
                            'cod' => $responseFromOpenWeather['decode_response']['cod'],
                            'message' => $responseFromOpenWeather['decode_response']['message'],
                        ]
                    ],
                    __DIR__ . '/../open-weather-api-error.txt'
                );
                $response = [
                    'status' => 'error',
                    'text' => 'Проблема с доступом к сервису "Open Weather"',
                    'icon' => null,
                ];
                break;
            case 404:
                self::writeToLogs(
                    [
                        'open_weather' => [
                            'user_city_request' => $city,
                            'cod' => $responseFromOpenWeather['decode_response']['cod'],
                            'message' => $responseFromOpenWeather['decode_response']['message'],
                        ]
                    ],
                    __DIR__ . '/../open-weather-api-error.txt'
                );
                $response = [
                    'status' => 'error',
                    'text' => 'Город не найден',
                    'icon' => null,
                ];
                break;
            case 200:
                self::writeToLogs(
                    [
                        'open_weather' => [
                            'user_city_request' => $city,
                            'response' => $responseFromOpenWeather['decode_response'],
                        ]
                    ],
                    __DIR__ . '/../open-weather-api-response.txt'
                );

                $response = [
                    'status' => 'success',
                    'text' => self::createTextForSuccessResponse($responseFromOpenWeather['decode_response'], $fromLang),
                    'icon' => $responseFromOpenWeather['decode_response']['weather'][0]['icon'],
                ];
                break;
            default:
                self::writeToLogs(
                    [
                        'open_weather' => [
                            'error' => 'Неизвестная ошибка',
                            'status' => $responseFromOpenWeather['status'],
                            'user_city_request' => $city,
                            'decode_response' => $responseFromOpenWeather['decode_response'],
                        ]
                    ],
                    __DIR__ . '/../open-weather-api-error.txt'
                );
                $response = [
                    'status' => 'error',
                    'text' => 'Бот временно не доступен, повторите попытку позднее',
                    'icon' => null,
                ];
                break;
        }

        return $response;
    }

    /**
     * @throws GuzzleException
     */
    private static function getForecastByLocation(
        string $method,
        array $location,
        string $fromLang,
    ): array {
        $responseFromOpenWeather = self::getResponseFromOpenWeather($method, [
            'query' => [
                'appid' => OPEN_WEATHER_MAP_TOKEN,
                'lat' => trim($location['latitude']),
                'lon' => trim($location['longitude']),
                'units' => ('ru' === $fromLang) ? 'metric' : 'imperial',
                'lang' => trim($fromLang),
            ],
        ]);

        if (200 === $responseFromOpenWeather['status']) {
            self::writeToLogs(
                [
                    'open_weather' => [
                        'user_location_request' => $location,
                        'response' => $responseFromOpenWeather['decode_response'],
                    ]
                ],
                __DIR__ . '/../open-weather-api-response.txt'
            );

            $response = [
                'status' => 'success',
                'text' => self::createTextForSuccessResponse($responseFromOpenWeather['decode_response'], $fromLang),
                'icon' => $responseFromOpenWeather['decode_response']['weather'][0]['icon'],
            ];
        } else {
            self::writeToLogs(
                [
                    'cod' => $responseFromOpenWeather['decode_response']['cod'],
                    'decode_message' => $responseFromOpenWeather['decode_response']['cod'],
                    'decode_response' => $responseFromOpenWeather['decode_response'],
                ],
                __DIR__ . '/../open-weather-api-error.txt'
            );

            $response = [
                'status' => 'error',
                'text' => 'Бот временно не доступен, повторите попытку позднее',
                'icon' => null,
            ];
        }

        return $response;
    }

    private static function createTextForSuccessResponse(array $decodeResponse, string $fromLang): string
    {
        $weatherMain = $decodeResponse['main'];
        $weatherMainTemp = round($weatherMain['temp']);
        $weatherSymbol = self::getWeatherSymbol($fromLang);

        return <<< OPEN_WEATHER_RESPONSE
                🔸️ ID в сервисе OpenWeather: <code>{$decodeResponse['id']}</code>
                🔸️ Страна: <code>{$decodeResponse['sys']['country']}</code>
                🔸️ Город: <code>{$decodeResponse['name']}</code>
                🔸️ Координаты: <code>{$decodeResponse['coord']['lon']},{$decodeResponse['coord']['lat']}</code>

                Информация о погоде: 
                    🔸️ Состояние: {$decodeResponse['weather'][0]['description']}
                    🔸️ Температура: {$weatherMainTemp}$weatherSymbol
                    🔸️ Влажность: {$weatherMain['humidity']}%
                OPEN_WEATHER_RESPONSE;
    }

    /**
     * @throws GuzzleException
     */
    private static function getResponseFromOpenWeather(string $method, ?array $additionalParams = null): ?array
    {
        $additionalParams['http_errors'] = false;

        $response = (new Client())->request(
            $method,
            OPEN_WEATHER_MAP_URL,
            $additionalParams
        );

        $status = $response->getStatusCode();
        $stream = $response->getBody();
        $jsonResponse = $stream->getContents();

        return [
            'decode_response' => json_decode($jsonResponse, true),
            'status' => $status
        ];
    }

    private static function getWeatherSymbol(string $fromLang): string
    {
        return ('ru' === $fromLang) ? '℃' : '℉';
    }
}
