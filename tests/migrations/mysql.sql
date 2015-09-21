/**
 * MySQL
 */

DROP TABLE IF EXISTS `post`;

CREATE TABLE `post` (
  `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `author_id` INT(11) NOT NULL DEFAULT 0
);

DROP TABLE IF EXISTS `post_translation`;

CREATE TABLE `post_translation` (
  `model_id`  INT(11)      NOT NULL,
  `language_id`  INT(11)      NOT NULL,
  `is_published` INT(11) NOT NULL DEFAULT 1,
  `title`    VARCHAR(255) NOT NULL,
  `body`     TEXT         NOT NULL,
  PRIMARY KEY (`model_id`, `language_id`)
);
