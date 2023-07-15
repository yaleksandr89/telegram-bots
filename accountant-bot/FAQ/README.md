**finance_cats**

* type - если **1 - категория расхода**, если **2 - категория дохода**.

---

1. Создать таблицу `finance_types` в БД `telegram_accountant`:

```sql
CREATE TABLE `telegram_accountant_bot`.`finance_types`
(
    `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(510) NOT NULL,

    PRIMARY KEY (`id`)
) ENGINE = InnoDB;
```

2. Добавить данные в таблицу: `finance_types`

```sql
INSERT INTO `telegram_accountant_bot`.`finance_types` (`id`, `title`)
VALUES (NULL, 'Доход'),
       (NULL, 'Расход');
```

3. Создание таблицы `finance` в БД `telegram_accountant`:

```sql
DROP TABLE IF EXISTS `telegram_accountant_bot`.`finance_cats`;
CREATE TABLE `telegram_accountant_bot`.`finance_cats`
(
    `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`   VARCHAR(510) NOT NULL,
    `type_id` INT UNSIGNED NOT NULL,

    PRIMARY KEY (`id`),
    FOREIGN KEY (`type_id`) REFERENCES `telegram_accountant_bot`.`finance_types` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB;
```

4. Создание таблицы `finance_cats` в БД `telegram_accountant`:

```sql
INSERT INTO `telegram_accountant_bot`.`finance_cats` (`id`, `title`, `type_id`)
VALUES (1, 'Зарплата', 2),
       (2, 'Другие', 2),
       (3, 'Жилье', 1),
       (4, 'Коммунальные услуги', 1),
       (5, 'Еда', 1),
       (6, 'Проезд', 1),
       (7, 'Интернет', 1),
       (8, 'Сотовая связь', 1),
       (9, 'Одежда', 1),
       (10, 'Медикаменты', 1),
       (11, 'Проценты по кредитам', 1),
       (12, 'Хозяйственные расходы', 1),
       (13, 'Покупка техники', 1),
       (14, 'Парикмахерская', 1),
       (15, 'Развлечения и отдых', 1),
       (16, 'Обучение', 1),
       (17, 'Подарки и дни рождения', 1),
       (18, 'Прочие', 1);
```

5. Наполнение таблицы `telegram_accountant_bot`.`finance_cats` данными

```sql
DROP TABLE IF EXISTS `telegram_accountant_bot`.`finance`;
CREATE TABLE `telegram_accountant_bot`.`finance`
(
    `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `amount`      DOUBLE UNSIGNED  NOT NULL,
    `category_id` INT UNSIGNED     NOT NULL,
    `type_id`     INT UNSIGNED     NOT NULL,
    `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    FOREIGN KEY (`category_id`) REFERENCES `telegram_accountant_bot`.`finance_cats` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`type_id`) REFERENCES `telegram_accountant_bot`.`finance_types` (`id`) ON DELETE RESTRICT
) ENGINE = InnoDB;
```
