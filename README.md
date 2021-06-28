```sql
CREATE TABLE `tiktok_poster_api`.`tiktok_account` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(255) NOT NULL,
    `fb_login` VARCHAR(255) NOT NULL,
    `fb_password` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;
```