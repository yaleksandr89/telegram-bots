<?php

namespace FirstBot;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class EchoTelegramBot
{
    /** @var Client $client */
    private Client $client;

    /** @var string $token */
    private string $token;

    /** @var string $baseUrl */
    private string $baseUrl;

    /** @var int $updateId */
    private int $updateId;

    /**
     * @param Client $client
     * @param string $token
     * @param string $baseUrl
     */
    public function __construct(Client $client, string $token, string $baseUrl)
    {
        $this->client = $client;
        $this->token = $token;
        $this->baseUrl = $baseUrl;
    }

    /**
     * @param bool $addToLog
     * @return array
     * @throws GuzzleException
     */
    public function getUpdates(bool $addToLog = false): array
    {
        $params = [];

        if (isset($this->updateId)) {
            $params = [
                'query' => [
                    'offset' => $this->updateId + 1,
                ],
            ];
        }

        $result = $this->client
            ->get($this->getBaseUrl() . 'getUpdates', $params)
            ->getBody()
            ->getContents();

        $result = json_decode($result, true);
        $result = $result['result'];
        $countElement = count($result);

        if ($countElement > 0) {
            $this->updateId = $result[$countElement - 1]['update_id'];

            if ($addToLog) {
                $this->addToLogs($result);
            }
        }

        return $result;
    }

    /**
     * @param int $chatId
     * @param string $text
     * @param array $params
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function sendMessage(int $chatId, string $text, array $params = []): ResponseInterface
    {
        return $this->client
            ->get($this->getBaseUrl() . 'sendMessage', [
                'query' => [
                    'chat_id' => $chatId,
                    'text' => $text
                ],
            ]);
    }

    /**
     * @return string
     */
    private function getBaseUrl(): string
    {
        return $this->baseUrl . $this->token . '/';
    }

    /**
     * @param array $message
     * @return void
     */
    private function addToLogs(array $message): void
    {
        if ((count($message) > 0)) {
            ob_start();
            echo '------------------' . PHP_EOL;
            foreach ($message as $item) {
                if (array_key_exists('message', $item)) {
                    echo '===[' . date('d-m-Y H:i:s', $item['message']['date']) . ']===' . PHP_EOL;
                }

                if (array_key_exists('edited_message', $item)) {
                    echo '===[' . date('d-m-Y H:i:s', $item['edited_message']['edit_date']) . ']===' . PHP_EOL;
                }

                print_r($item);
            }
            echo PHP_EOL . '------------------';
        }
        $log = ob_get_clean();

        if ('' !== $log) {
            file_put_contents(__DIR__ . '/logs-messages.txt', $log, FILE_APPEND);
        }
    }
}
