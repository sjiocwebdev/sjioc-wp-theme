-- ============================================================
--  SJIOC Delaware Valley — Member Registry
--  Table: wp_sjioc_members
--
--  Run via:
--    phpMyAdmin  → select your WP database → SQL tab → paste & run
--    WP-CLI:  wp db query < SQL_MEMBERS.sql
--    Azure:   mysql -h <host> -u <user> -p<pass> <db> < SQL_MEMBERS.sql
--
--  Column names match the Excel export headers exactly:
--    phone_number, zip_code
--  marital_status codes: M=Married  S=Single  W=Widowed  D=Divorced
--  Date format in source data: DD/MM/YYYY — use STR_TO_DATE on import
-- ============================================================

DROP TABLE IF EXISTS `wp_sjioc_members`;

CREATE TABLE `wp_sjioc_members` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,

  -- Family grouping: cardex_no identifies the family (e.g. A-06),
  -- member_seq is the position within the family (1=head, 2=spouse, 3+=children)
  `cardex_no`      VARCHAR(20)      NOT NULL,
  `member_seq`     TINYINT UNSIGNED NOT NULL DEFAULT 1,

  -- Name (column order matches Excel headers)
  `first_name`     VARCHAR(50)      NOT NULL,
  `middle_name`    VARCHAR(50)               DEFAULT NULL,
  `last_name`      VARCHAR(50)      NOT NULL,

  -- Key dates
  `date_of_birth`  DATE                      DEFAULT NULL,   -- birthday celebrations

  -- Marital info (M=Married S=Single W=Widowed D=Divorced)
  `marital_status` ENUM('M','S','W','D')     DEFAULT NULL,
  `wedding_date`   DATE                      DEFAULT NULL,   -- anniversary celebrations

  -- Demographics
  `gender`         ENUM('M','F','O')         DEFAULT NULL,

  -- Contact (named to match Excel column headers)
  `phone_number`   VARCHAR(30)               DEFAULT NULL,
  `email`          VARCHAR(100)              DEFAULT NULL,

  -- Address
  `address`        VARCHAR(150)              DEFAULT NULL,
  `city`           VARCHAR(80)               DEFAULT NULL,
  `state`          VARCHAR(50)               DEFAULT NULL,
  `zip_code`       VARCHAR(20)               DEFAULT NULL,
  `country`        VARCHAR(60)               DEFAULT 'USA',

  -- Status
  `is_active`      TINYINT(1)       NOT NULL DEFAULT 1,

  -- Timestamps
  `created_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cardex_seq`  (`cardex_no`, `member_seq`),
  KEY `idx_dob`               (`date_of_birth`),
  KEY `idx_wed`               (`wedding_date`),
  KEY `idx_active`            (`is_active`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  Sample data (39 members, 10 families)
--  Dates converted via STR_TO_DATE from DD/MM/YYYY source
-- ============================================================

INSERT INTO `wp_sjioc_members`
  (`cardex_no`,`member_seq`,`first_name`,`middle_name`,`last_name`,
   `date_of_birth`,`marital_status`,`wedding_date`,`gender`,
   `phone_number`,`email`,`address`,`city`,`state`,`zip_code`,`country`)
VALUES
-- ── Family A-06 ──────────────────────────────────────────
('A-06',1,'Alexander','Manappallil','Joy',
  STR_TO_DATE('1/9/1970','%d/%m/%Y'),'M',STR_TO_DATE('17/12/2011','%d/%m/%Y'),
  'M','302-981-1827',NULL,'1803 Mandarin Ct','New Castle','DE','19720','USA'),
('A-06',2,'Annu',NULL,'Alexander',
  STR_TO_DATE('4/3/1970','%d/%m/%Y'),'M',STR_TO_DATE('17/12/2011','%d/%m/%Y'),
  'F',NULL,NULL,'1803 Mandarin Ct','New Castle','DE','19720','USA'),
('A-06',3,'David',NULL,'Alexander',
  STR_TO_DATE('18/11/2002','%d/%m/%Y'),'S',NULL,
  'M',NULL,NULL,'1803 Mandarin Ct','New Castle','DE','19720','USA'),
('A-06',4,'Leah',NULL,'Alexander',
  STR_TO_DATE('2/9/2004','%d/%m/%Y'),NULL,NULL,
  'F',NULL,NULL,'1803 Mandarin Ct','New Castle','DE','19720','USA'),

-- ── Family A-07 ──────────────────────────────────────────
('A-07',1,'Abraham',NULL,'Thomas',
  STR_TO_DATE('12/6/1980','%d/%m/%Y'),'M',STR_TO_DATE('10/2/2010','%d/%m/%Y'),
  'M','610-990-9710',NULL,'107 Fox Hollow Lane','Broomall','PA','19008','USA'),
('A-07',2,'Bini',NULL,'Joseph',
  STR_TO_DATE('11/9/1984','%d/%m/%Y'),'M',STR_TO_DATE('10/2/2010','%d/%m/%Y'),
  'F',NULL,NULL,'107 Fox Hollow Lane','Broomall','PA','19008','USA'),
('A-07',3,'Elsi',NULL,'Thomas',
  STR_TO_DATE('10/1/1949','%d/%m/%Y'),NULL,NULL,
  'F',NULL,NULL,'107 Fox Hollow Lane','Broomall','PA','19008','USA'),
('A-07',4,'Saira',NULL,'Abraham',
  STR_TO_DATE('17/9/2014','%d/%m/%Y'),'S',NULL,
  'F',NULL,NULL,'107 Fox Hollow Lane','Broomall','PA','19008','USA'),
('A-07',5,'Hannah',NULL,'Abraham',
  STR_TO_DATE('1/7/2021','%d/%m/%Y'),NULL,NULL,
  'F',NULL,NULL,'107 Fox Hollow Lane','Broomall','PA','19008','USA'),

-- ── Family A-10 ──────────────────────────────────────────
('A-10',1,'Avinash','Philip','Pesara',
  STR_TO_DATE('10/2/1986','%d/%m/%Y'),'M',STR_TO_DATE('4/5/2014','%d/%m/%Y'),
  'M','703-347-4487',NULL,'41 Ryan Ct','Gilbertsville','PA','19525','USA'),
('A-10',2,'Annie',NULL,'Thomas',
  STR_TO_DATE('18/5/1986','%d/%m/%Y'),'M',STR_TO_DATE('4/5/2014','%d/%m/%Y'),
  'F','703-347-4487',NULL,'41 Ryan Ct','Gilbertsville','PA','19525','USA'),
('A-10',3,'Avin','Thomas','Pesara',
  STR_TO_DATE('20/7/2016','%d/%m/%Y'),NULL,NULL,
  'M',NULL,NULL,'41 Ryan Ct','Gilbertsville','PA','19525','USA'),

-- ── Family A-11 ──────────────────────────────────────────
('A-11',1,'Aju','K','Alex',
  STR_TO_DATE('3/8/1982','%d/%m/%Y'),'M',STR_TO_DATE('16/7/2012','%d/%m/%Y'),
  'M','617-99-10512',NULL,'635 Willowbrook Dr','Norristown','PA','19403','USA'),
('A-11',2,'Tintu','Mariyam','Thomas',
  STR_TO_DATE('13/10/1989','%d/%m/%Y'),'M',STR_TO_DATE('16/7/2012','%d/%m/%Y'),
  'F',NULL,NULL,'635 Willowbrook Dr','Norristown','PA','19403','USA'),
('A-11',3,'Tabitha','Rose','Aju',
  STR_TO_DATE('16/12/2013','%d/%m/%Y'),NULL,NULL,
  'F',NULL,NULL,'635 Willowbrook Dr','Norristown','PA','19403','USA'),
('A-11',4,'Micah','Mariam','Aju',
  STR_TO_DATE('9/9/2017','%d/%m/%Y'),NULL,NULL,
  'F',NULL,NULL,'635 Willowbrook Dr','Norristown','PA','19403','USA'),

-- ── Family A-12 ──────────────────────────────────────────
('A-12',1,'Arun',NULL,'Sunny',
  STR_TO_DATE('29/7/1986','%d/%m/%Y'),'M',STR_TO_DATE('9/9/2012','%d/%m/%Y'),
  'M','267-403-6135',NULL,'377 Avon Rd, Apt D 118','Devon','PA','19333','USA'),
('A-12',2,'Meenu','Susan','Lalu',
  STR_TO_DATE('31/5/1986','%d/%m/%Y'),'M',STR_TO_DATE('9/9/2012','%d/%m/%Y'),
  'F','267-403-6135',NULL,'377 Avon Rd, Apt D 118','Devon','PA','19333','USA'),
('A-12',3,'Alvin',NULL,'Arun',
  STR_TO_DATE('26/5/2014','%d/%m/%Y'),NULL,NULL,
  'M',NULL,NULL,'377 Avon Rd, Apt D 118','Devon','PA','19333','USA'),

-- ── Family B-02 ──────────────────────────────────────────
('B-02',1,'Bensen',NULL,'Mathew',
  STR_TO_DATE('15/3/1959','%d/%m/%Y'),'M',STR_TO_DATE('16/7/1990','%d/%m/%Y'),
  'M',NULL,NULL,'2504 Highland Ave','Broomall','PA','19008','USA'),
('B-02',2,'Annamma',NULL,'Bensen',
  STR_TO_DATE('29/3/1967','%d/%m/%Y'),'M',STR_TO_DATE('16/7/1990','%d/%m/%Y'),
  'F',NULL,NULL,'2504 Highland Ave','Broomall','PA','19008','USA'),
('B-02',3,'Jinson',NULL,'Thomas',
  STR_TO_DATE('31/12/1993','%d/%m/%Y'),'M',STR_TO_DATE('29/11/2021','%d/%m/%Y'),
  'M',NULL,NULL,'2504 Highland Ave','Broomall','PA','19008','USA'),
('B-02',4,'Agi','Mariam','Bensen',
  STR_TO_DATE('24/6/1995','%d/%m/%Y'),'M',STR_TO_DATE('29/11/2021','%d/%m/%Y'),
  'F',NULL,NULL,'2504 Highland Ave','Broomall','PA','19008','USA'),
('B-02',5,'Ajesh','Mathew','Bensen',
  STR_TO_DATE('21/12/2002','%d/%m/%Y'),'S',NULL,
  'M',NULL,NULL,'2504 Highland Ave','Broomall','PA','19008','USA'),

-- ── Family B-06 ──────────────────────────────────────────
('B-06',1,'Binu',NULL,'Mathew',
  STR_TO_DATE('24/4/1900','%d/%m/%Y'),'M',STR_TO_DATE('16/11/2000','%d/%m/%Y'),
  'M','636-544-2865',NULL,'843 Tremont Dr','Downingtown','PA','19335','USA'),
('B-06',2,'Giby',NULL,'Mathew',
  STR_TO_DATE('30/5/1900','%d/%m/%Y'),'M',STR_TO_DATE('16/11/2000','%d/%m/%Y'),
  'F',NULL,NULL,'843 Tremont Dr','Downingtown','PA','19335','USA'),
('B-06',3,'Naithan',NULL,'Mathew',
  STR_TO_DATE('10/2/2006','%d/%m/%Y'),'S',NULL,
  'M',NULL,NULL,'843 Tremont Dr','Downingtown','PA','19335','USA'),
('B-06',4,'Norah',NULL,'Mathew',
  STR_TO_DATE('21/10/2010','%d/%m/%Y'),'S',NULL,
  'F',NULL,NULL,'843 Tremont Dr','Downingtown','PA','19335','USA'),

-- ── Family B-07 ──────────────────────────────────────────
('B-07',1,'Bijoy',NULL,'Mathunni',
  STR_TO_DATE('23/4/1974','%d/%m/%Y'),'M',STR_TO_DATE('28/10/2001','%d/%m/%Y'),
  'M',NULL,NULL,'12 Madison Way','Downingtown','PA','19335','USA'),
('B-07',2,'Priya',NULL,'Uthup',
  STR_TO_DATE('6/11/1975','%d/%m/%Y'),'M',STR_TO_DATE('28/10/2001','%d/%m/%Y'),
  'F',NULL,NULL,'12 Madison Way','Downingtown','PA','19335','USA'),
('B-07',3,'Sneha',NULL,'Bijoy',
  STR_TO_DATE('9/3/2004','%d/%m/%Y'),'S',NULL,
  'F',NULL,NULL,'12 Madison Way','Downingtown','PA','19335','USA'),

-- ── Family B-09 ──────────────────────────────────────────
('B-09',1,'Biju',NULL,'Varghese',
  STR_TO_DATE('30/5/1972','%d/%m/%Y'),'M',STR_TO_DATE('24/4/2000','%d/%m/%Y'),
  'M',NULL,NULL,'3900 City Avenue, J 1021','Philadelphia','PA','19131','USA'),
('B-09',2,'Teena',NULL,'Biju',
  STR_TO_DATE('21/4/1976','%d/%m/%Y'),'M',STR_TO_DATE('24/4/2000','%d/%m/%Y'),
  'F',NULL,NULL,'3900 City Avenue, J 1021','Philadelphia','PA','19131','USA'),
('B-09',3,'Meeval',NULL,'Biju',
  STR_TO_DATE('3/1/2003','%d/%m/%Y'),NULL,NULL,
  'F',NULL,NULL,'3900 City Avenue, J 1021','Philadelphia','PA','19131','USA'),
('B-09',4,'Merril',NULL,'Biju',
  STR_TO_DATE('20/12/2005','%d/%m/%Y'),NULL,NULL,
  'F',NULL,NULL,'3900 City Avenue, J 1021','Philadelphia','PA','19131','USA'),

-- ── Family B-10 ──────────────────────────────────────────
('B-10',1,'Biju',NULL,'John',
  STR_TO_DATE('6/2/1971','%d/%m/%Y'),'M',STR_TO_DATE('21/10/2007','%d/%m/%Y'),
  'M',NULL,NULL,'177 Bowery Ln','Downingtown','PA','19335','USA'),
('B-10',2,'Biji','B','John',
  STR_TO_DATE('17/12/1973','%d/%m/%Y'),'M',STR_TO_DATE('21/10/2007','%d/%m/%Y'),
  'F',NULL,NULL,'177 Bowery Ln','Downingtown','PA','19335','USA'),
('B-10',3,'Jeshurun','B','John',
  STR_TO_DATE('14/10/2008','%d/%m/%Y'),NULL,NULL,
  'M',NULL,NULL,'177 Bowery Ln','Downingtown','PA','19335','USA');
