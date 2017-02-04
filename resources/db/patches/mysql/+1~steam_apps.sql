#Create the new steam_apps table

DROP TABLE IF EXISTS steam_apps;
CREATE TABLE steam_apps (
  name         VARCHAR(191)        NOT NULL COMMENT 'Steam application name',
  appid        INT(11) UNSIGNED    NULL COMMENT 'Steam application id'
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
