<?php

namespace AccountantBot\Utils;

use PDO;
use PDOException;
use PDOStatement;

final class DB
{
    private ?PDO $connection = null;

    private PDOStatement $stmt;

    private static ?DB $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }

    public static function getInstance(): DB
    {
        return self::$instance ?? (self::$instance = new self());
    }

    public function getConnection(array $dbConfig): DB
    {
        if ($this->connection instanceof PDO) {
            return $this;
        }

        $dsn = "{$dbConfig['type']}:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']}";

        try {
            $this->connection = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], $dbConfig['options']);
        } catch (PDOException $exception) {
            file_put_contents(
                __DIR__ . '/../try_catch_db_logs.txt',
                date('d.m.Y H:i:s') . PHP_EOL . print_r($exception, true),
                FILE_APPEND
            );
            die;
        }

        return $this;
    }

    public function query(string $query, array $params = []): false|DB
    {
        try {
            $this->stmt = $this->connection->prepare($query);
            $this->stmt->execute($params);
        } catch (PDOException $exception) {
            file_put_contents(
                __DIR__ . '/../try_catch_db_logs.txt',
                date('d.m.Y H:i:s') . PHP_EOL . print_r($exception, true),
                FILE_APPEND
            );
            die;
        }

        return $this;
    }

    public function findAll(): array
    {
        return $this
            ->stmt
            ->fetchAll();
    }

    public function find(): array
    {
        $result = $this
            ->stmt
            ->fetch();

        if (!$result) {
            return [];
        }

        return $result;
    }

    public function getAllCategories(): array
    {
        return $this
            ->query('SELECT * FROM `telegram_accountant_bot`.finance_cats')
            ->findAll();
    }

    public function getCategoriesForType(int $type): array
    {
        return $this
            ->query(
                'SELECT * FROM `telegram_accountant_bot`.finance_cats WHERE type=:type',
                [
                    'type' => $type
                ]
            )
            ->findAll();
    }

//    public function getChatId(int $chatId): ?array
//    {
//        return $this
//            ->query('SELECT * FROM chat WHERE chat_id=:chatId', ['chatId' => $chatId])
//            ->find();
//    }
//    public function setChat(
//        int $chatId,
//        string $firstName,
//        string $lastName,
//        string $username,
//        string $date,
//        string $lang
//    ): void {
//        $this
//            ->query(
//                'INSERT INTO chat
//                        (chat_id, first_name, last_name, username, date, lang)
//                        VALUES (:chatId, :firstName, :lastName, :username, :date, :lang)',
//                [
//                    'chatId' => $chatId,
//                    'firstName' => $firstName,
//                    'lastName' => $lastName,
//                    'username' => $username,
//                    'date' => $date,
//                    'lang' => $lang
//                ]
//            );
//    }
//    public function updateChat(int $chatId, string $lang, string $date): void
//    {
//        $this
//            ->query(
//                'UPDATE chat
//                       SET lang=:lang, date=:date
//                       WHERE chat_id=:chatId',
//                [
//                    'chatId' => $chatId,
//                    'lang' => $lang,
//                    'date' => $date
//                ]
//            );
//    }
}
