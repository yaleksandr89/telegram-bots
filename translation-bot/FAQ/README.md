Создание таблицы `chat` в БД `telegram_bot_translation`:

```sql
CREATE TABLE `telegram_bot_translation`.`chat`
(
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `chat_id`    BIGINT UNSIGNED NOT NULL,
    `first_name` VARCHAR(510)    NOT NULL,
    `last_name`  VARCHAR(510)    NOT NULL,
    `username`   VARCHAR(510)    NOT NULL,
    `date`       DATETIME        NOT NULL,
    `lang`       VARCHAR(50)     NOT NULL,

    PRIMARY KEY (`id`),
    UNIQUE (`chat_id`)
)
    ENGINE = InnoDB;
```
