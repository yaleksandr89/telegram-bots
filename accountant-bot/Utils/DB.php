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

    public function find(): ?array
    {
        $result = $this
            ->stmt
            ->fetch();

        if (!$result) {
            return null;
        }

        return $result;
    }

    public function getAllCategories(): array
    {
        return $this
            ->query('SELECT * FROM `telegram_accountant_bot`.finance_cats')
            ->findAll();
    }

    public function getCategoriesForType(int $typeId): array
    {
        return $this
            ->query(
                'SELECT * FROM `telegram_accountant_bot`.finance_cats WHERE type_id = :typeId',
                [
                    'typeId' => $typeId
                ]
            )
            ->findAll();
    }

    public function isCategory(int $typeId, string $category): bool
    {
        $isCategory = $this
            ->query(
                'SELECT * FROM `telegram_accountant_bot`.finance_cats WHERE type_id = :typeId AND title = :title',
                [
                    'typeId' => $typeId,
                    'title' => trim($category)
                ]
            )
            ->find();

        return null !== $isCategory;
    }

    public function getCategoryByTitle(string $titleCategory): ?array
    {
        return $this
            ->query(
                'SELECT * FROM `telegram_accountant_bot`.finance_cats WHERE title = :title',
                [
                    'title' => trim($titleCategory)
                ]
            )
            ->find();
    }

    public function setFinance(int $typeId, float|int $amount, string $titleCategory): bool
    {
        $category = $this->getCategoryByTitle($titleCategory);

        if (!$category) {
            return false;
        }

        $insetItem = $this
            ->query(
                'INSERT INTO `telegram_accountant_bot`.`finance`
                        (amount, category_id, type_id)
                        VALUES (:amount, :categoryId, :typeId)',
                [
                    'amount' => $amount,
                    'categoryId' => $category['id'],
                    'typeId' => $typeId,
                ]
            );

        if (!$insetItem) {
            return false;
        }

        return true;
    }

    public function getFinanceInfoForTodayWithoutCategories(int $typeId): ?array
    {
        $sql = <<< SQL
                SELECT SUM(amount) AS amount
                FROM `telegram_accountant_bot`.`finance`
                WHERE type_id=:typeId AND DATE(created_at)=CURRENT_DATE()
                SQL;

        return $this
            ->query(
                $sql,
                ['typeId' => $typeId]
            )
            ->find();
    }

    public function getFinanceInfoForTodayWithCategories(int $typeId): ?array
    {
        $sql = <<< SQL
                SELECT fc.title AS category, SUM(f.amount) AS amount
                FROM `telegram_accountant_bot`.`finance` AS f
                LEFT JOIN `telegram_accountant_bot`.`finance_cats` AS fc ON f.category_id = fc.id
                WHERE f.type_id = :typeId AND DATE(f.created_at) = CURRENT_DATE()
                GROUP BY category;
                SQL;

        return $this
            ->query(
                $sql,
                ['typeId' => $typeId]
            )
            ->findAll();
    }

    public function getFinanceInfoForMonthWithoutCategories(int $typeId): ?array
    {
        $sql = <<< SQL
                SELECT SUM(amount) AS amount
                FROM `telegram_accountant_bot`.`finance`
                WHERE type_id = :typeId AND (YEAR(created_at) = YEAR(CURRENT_DATE) AND MONTH(created_at) = MONTH(CURRENT_DATE));
                SQL;

        return $this
            ->query(
                $sql,
                ['typeId' => $typeId]
            )
            ->find();
    }

    public function getFinanceInfoForMonthWithCategories(int $typeId): array
    {
        $sql = <<< SQL
                SELECT fc.title AS category, SUM(f.amount) AS amount
                FROM `telegram_accountant_bot`.`finance` AS f
                LEFT JOIN `telegram_accountant_bot`.`finance_cats` AS fc ON f.category_id = fc.id
                WHERE f.type_id = :typeId AND (YEAR(created_at) = YEAR(CURRENT_DATE) AND MONTH(created_at) = MONTH(CURRENT_DATE))
                GROUP BY category;
                SQL;

        return $this
            ->query(
                $sql,
                ['typeId' => $typeId]
            )
            ->findAll();
    }
}
