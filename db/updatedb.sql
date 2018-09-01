USE pizza;
SET collation_connection = 'utf8mb4_unicode_ci';

ALTER TABLE orderday ADD COLUMN IF NOT EXISTS 
  (`maildue` tinyint(1) NOT NULL,
  `mailready` tinyint(1) NOT NULL);

ALTER TABLE user ADD COLUMN IF NOT EXISTS
  (`notify_orderdue` tinyint(1) NOT NULL DEFAULT 1,
  `notify_orderready` tinyint(1) NOT NULL DEFAULT 1);
