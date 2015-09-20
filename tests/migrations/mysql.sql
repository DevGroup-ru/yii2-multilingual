/**
 * MySQL
 */

DROP TABLE IF EXISTS `post`;

CREATE TABLE `post` (
  `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `author_id` INT(11) NOT NULL DEFAULT 0
);

DROP TABLE IF EXISTS `post_translation_ru`;

CREATE TABLE `post_translation_ru` (
  `post_id`  INT(11)      NOT NULL,
  `is_published` INT(11) NOT NULL DEFAULT 1,
  `title`    VARCHAR(255) NOT NULL,
  `body`     TEXT         NOT NULL,
  PRIMARY KEY (`post_id`)
);


DROP TABLE IF EXISTS `post_translation_en`;

CREATE TABLE `post_translation_en` (
  `post_id`  INT(11)      NOT NULL,
  `is_published` INT(11) NOT NULL DEFAULT 1,
  `title`    VARCHAR(255) NOT NULL,
  `body`     TEXT         NOT NULL,
  PRIMARY KEY (`post_id`)
);


DROP TABLE IF EXISTS `post_translation_de`;

CREATE TABLE `post_translation_de` (
  `post_id`  INT(11)      NOT NULL,
  `is_published` INT(11) NOT NULL DEFAULT 1,
  `title`    VARCHAR(255) NOT NULL,
  `body`     TEXT         NOT NULL,
  PRIMARY KEY (`post_id`)
);
