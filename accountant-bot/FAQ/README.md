**finance_cats**

* type - если 0 - категория расхода, если 1 - категория дохода.

---

1. Создание таблицы `finance` в БД `telegram_accountant`:

```sql
DROP TABLE IF EXISTS `telegram_accountant_bot`.`finance`;
CREATE TABLE `telegram_accountant_bot`.`finance`
(
    `id`            INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `amount`        DOUBLE              NOT NULL,
    `category`      INT UNSIGNED        NOT NULL,
    `type`          TINYINT UNSIGNED    NOT NULL,
    `created_at`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB;
```

2. Создание таблицы `finance_cats` в БД `telegram_accountant`:

```sql
DROP TABLE IF EXISTS `telegram_accountant_bot`.`finance_cats`;
CREATE TABLE `telegram_accountant_bot`.`finance_cats`
(
    `id`            INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `title`         VARCHAR(510)              NOT NULL,
    `type`          TINYINT UNSIGNED    NOT NULL,

    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB;
```

3. Наполнение таблицы `telegram_accountant_bot`.`finance_cats` данными

```sql
INSERT INTO `telegram_accountant_bot`.`finance_cats` (`id`, `title`, `type`) VALUES
(1, 'Зарплата', 1),
(2, 'Другие', 1),
(3, 'Жилье', 0),
(4, 'Коммунальные услуги', 0),
(5, 'Еда', 0),
(6, 'Проезд', 0),
(7, 'Интернет', 0),
(8, 'Сотовая связь', 0),
(9, 'Одежда', 0),
(10, 'Медикаменты', 0),
(11, 'Проценты по кредитам', 0),
(12, 'Хозяйственные расходы', 0),
(13, 'Покупка техники', 0),
(14, 'Парикмахерская', 0),
(15, 'Развлечения и отдых', 0),
(16, 'Обучение', 0),
(17, 'Подарки и дни рождения', 0),
(18, 'Прочие', 0);
```
