USE pizza;
SET collation_connection = 'utf8mb4_unicode_ci';

ALTER TABLE orderday ADD COLUMN IF NOT EXISTS 
  (`maildue` tinyint(1) NOT NULL,
  `mailready` tinyint(1) NOT NULL);
