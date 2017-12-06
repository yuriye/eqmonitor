DROP TABLE IF EXISTS `#__eqm_queue_item`;
DROP TABLE IF EXISTS `#__eqm_filial`;
DROP TABLE IF EXISTS `#__eqm_filial_cabs`;

CREATE TABLE `#__eqm_queue_item` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `uuid`            VARCHAR(64) NOT NULL,
  `filial`          VARCHAR(128),
  `ticket`          VARCHAR(15),
  `priority`        VARCHAR(10),
  `queued_at`       INT(15),
  `call_time`       TIMESTAMP,
  `start_time`      INT(15),
  `remote_reg`      BOOL,
  `waiting_time`    INT(15),
  `queue`           VARCHAR(128),
  `service_name`    VARCHAR(128),
  `status`          VARCHAR(15),
  `window_number`   VARCHAR(10),
  `number_of_cases` INT(3),
  `created_on`      INT(15),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

CREATE TABLE `#__eqm_filial` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `uuid`            VARCHAR(64) NOT NULL,
  `filial`          VARCHAR(128)  NOT NULL,
  `cabs`    INT(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

CREATE TABLE `#__eqm_filial_cabs` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `uuid`            VARCHAR(64) NOT NULL,
  `filial`          VARCHAR(128)  NOT NULL,
  `cab_num`         INT(3) NOT NULL,
  `status`          VARCHAR(25) NOT NULL,
  `state`           VARCHAR(10) NOT NULL,
  `count_of_served` INT(4),
  `average_service_time` INT(10),
  `pause_starttime` INT(10),
  `pause_count`     INT(10),
  `dayoff`          BOOL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;


INSERT IGNORE INTO `#__eqm_filial` (uuid, filial, cabs)
VALUES
  ('396138c0-423c-4764-a2ad-3216481b1221', 'Петропавловск-Камчатский, Пограничная, 17', 19),
  ('0092f0d7-7e64-4674-a25b-0e2530d91851', 'Петропавловск-Камчатский, Савченко, 23', 15),
  ('e7469155-86c2-4e38-abc1-9ee62d2aab26', 'Петропавловск-Камчатский, Океанская, 94', 6),
  ('e5ccab36-24df-481e-b036-ee8252f3ec01', 'Елизово, Беринга, 9', 8),
  ('1fd1f61c-d7db-4cab-af93-d57b030fd855', 'Вилючинск, м-н Центральный, 5', 5);


