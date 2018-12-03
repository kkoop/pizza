USE pizza;
SET collation_connection = 'utf8mb4_unicode_ci';

ALTER TABLE orderday ADD COLUMN IF NOT EXISTS 
  (`maildue` tinyint(1) NOT NULL,
  `mailready` tinyint(1) NOT NULL);

ALTER TABLE user ADD COLUMN IF NOT EXISTS
  (`notify_orderdue` tinyint(1) NOT NULL DEFAULT 1,
  `notify_orderready` tinyint(1) NOT NULL DEFAULT 1);

CREATE TABLE IF NOT EXISTS `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menuitem` (
  `menu` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int(11) NOT NULL,
  PRIMARY KEY (`menu`,`sort`),
  CONSTRAINT `menuitem_ibfk_1` FOREIGN KEY (`menu`) REFERENCES `menu` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `upload` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user` int(11) NOT NULL,
 `title` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
 `created` timestamp NULL DEFAULT NULL,
 `expiry` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`id`),
 KEY `user` (`user`),
 CONSTRAINT `upload_ibfk_1` FOREIGN KEY (`user`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
