-- ============================================================
--  SJIOC Delaware Valley — Member Registry
--  Table: wp_sjioc_members
--
--  Run via:
--    phpMyAdmin  → select your WP database → SQL tab → paste & run
--    WP-CLI:  wp db query < SQL_MEMBERS.sql
--    Azure:   mysql -h <host> -u <user> -p<pass> <db> < SQL_MEMBERS.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `wp_sjioc_members` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,

  -- Family grouping: cardex_no identifies the family unit,
  -- member_seq identifies the individual within that family
  -- (1 = head of household, 2 = spouse, 3+ = children)
  `cardex_no`      VARCHAR(20)      NOT NULL,
  `member_seq`     TINYINT UNSIGNED NOT NULL DEFAULT 1,

  -- Name
  `first_name`     VARCHAR(50)      NOT NULL,
  `middle_name`    VARCHAR(50)               DEFAULT NULL,
  `last_name`      VARCHAR(50)      NOT NULL,

  -- Demographics
  `gender`         ENUM('M','F','O')         DEFAULT NULL,
  `date_of_birth`  DATE                      DEFAULT NULL,   -- used for birthday celebrations
  `marital_status` ENUM('single','married','widowed','divorced') DEFAULT NULL,
  `wedding_date`   DATE                      DEFAULT NULL,   -- used for anniversary celebrations (married couples)

  -- Contact
  `phone`          VARCHAR(20)               DEFAULT NULL,
  `email`          VARCHAR(100)              DEFAULT NULL,

  -- Address
  `address`        VARCHAR(150)              DEFAULT NULL,
  `city`           VARCHAR(80)               DEFAULT NULL,
  `state`          VARCHAR(50)               DEFAULT NULL,
  `zip`            VARCHAR(20)               DEFAULT NULL,
  `country`        VARCHAR(60)               DEFAULT 'USA',

  -- Status
  `is_active`      TINYINT(1)       NOT NULL DEFAULT 1,

  -- Timestamps
  `created_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- Each family member is unique within their family card
  UNIQUE KEY `uq_cardex_seq` (`cardex_no`, `member_seq`),

  -- Fast lookups for celebrations cron (month + day matching)
  KEY `idx_dob`    (`date_of_birth`),
  KEY `idx_wed`    (`wedding_date`),
  KEY `idx_active` (`is_active`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  Sample data — remove before production
-- ============================================================

INSERT IGNORE INTO `wp_sjioc_members`
  (`cardex_no`, `member_seq`, `first_name`, `middle_name`, `last_name`,
   `gender`, `date_of_birth`, `marital_status`, `wedding_date`,
   `phone`, `email`, `city`, `state`, `zip`, `is_active`)
VALUES
  ('C001', 1, 'Thomas',   'K',   'Philip',   'M', '1968-05-07', 'married', '1995-06-15', '(610) 555-0101', 'thomas@example.com',  'Drexel Hill', 'PA', '19026', 1),
  ('C001', 2, 'Mary',     NULL,  'Philip',   'F', '1972-03-22', 'married', '1995-06-15', '(610) 555-0101', 'mary@example.com',    'Drexel Hill', 'PA', '19026', 1),
  ('C001', 3, 'Latha',    NULL,  'Philip',   'F', '1998-05-07', 'single',   NULL,        '(610) 555-0102', NULL,                  'Drexel Hill', 'PA', '19026', 1),

  ('C002', 1, 'Mathew',   'C',   'George',   'M', '1975-09-14', 'married', '2016-06-03', '(610) 555-0201', 'mathew@example.com',  'Exton',       'PA', '19341', 1),
  ('C002', 2, 'Susan',    NULL,  'George',   'F', '1978-11-30', 'married', '2016-06-03', '(610) 555-0201', 'susan@example.com',   'Exton',       'PA', '19341', 1),

  ('C003', 1, 'George',   'V',   'Varghese', 'M', '1960-05-20', 'married', '1988-08-10', '(610) 555-0301', NULL,                  'West Chester','PA', '19380', 1),
  ('C003', 2, 'Annamma',  NULL,  'Varghese', 'F', '1963-07-04', 'married', '1988-08-10', '(610) 555-0301', NULL,                  'West Chester','PA', '19380', 1),

  ('C004', 1, 'Jacob',    'M',   'Cherian',  'M', '1955-12-01', 'married', '2011-07-22', '(610) 555-0401', NULL,                  'Springfield', 'PA', '19064', 1),
  ('C004', 2, 'Sosamma',  NULL,  'Cherian',  'F', '1958-06-18', 'married', '2011-07-22', '(610) 555-0401', NULL,                  'Springfield', 'PA', '19064', 1),

  ('C005', 1, 'Philip',   'A',   'Abraham',  'M', '1985-07-04', 'single',   NULL,        '(610) 555-0501', NULL,                  'King of Prussia','PA','19406',1);
