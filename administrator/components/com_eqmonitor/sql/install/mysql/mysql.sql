DROP TABLE IF EXISTS `#__queue_item`;
DROP TABLE IF EXISTS `#__filial`;

CREATE TABLE `#__eqm_queue_item` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `filial`          VARCHAR(128)  NOT NULL,
  `ticket`          VARCHAR(15) NOT NULL,
  `priority`        VARCHAR(10),
  `queued_at`       DATETIME,
  `call_time`       DATETIME,
  `waiting_time`    DATETIME,
  `queue`           VARCHAR(128),
  `service_name`    VARCHAR(128),
  `status`          VARCHAR(15),
  `window_number`   VARCHAR(10),
  `number_of_cases` INT(3),
  `created_on`      DATETIME  NOT NULL COMMENT 'Time the record is created.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

CREATE TABLE `#__eqm_filial` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `uuid`            VARCHAR(64) NOT NULL,
  `filial`          VARCHAR(128)  NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

INSERT IGNORE INTO `#__eqm_filial` (uuid, filial)
VALUES
  ('396138c0-423c-4764-a2ad-3216481b1221', 'Петропавловск-Камчатский, Пограничная, 17'),
  ('0092f0d7-7e64-4674-a25b-0e2530d91851', 'Петропавловск-Камчатский, Савченко, 23'),
  ('fc272f42-3607-487d-9093-219bee24d557', 'Петропавловск-Камчатский, Рыбаков, 13'),
  ('e7469155-86c2-4e38-abc1-9ee62d2aab26', 'Петропавловск-Камчатский, Океанская, 94'),
  ('e5ccab36-24df-481e-b036-ee8252f3ec01', 'Елизово, Беринга, 9'),
  ('1fd1f61c-d7db-4cab-af93-d57b030fd855', 'Вилючинск, м-н Центральный, 5')
