-- SJIOC Vehicle Registry Table
-- Run once against your WordPress database
-- Replace `wp_` with your actual WP table prefix if different

CREATE TABLE IF NOT EXISTS `wp_sjioc_vehicles` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `license_plate` VARCHAR(20)     NOT NULL,
  `owner_name`    VARCHAR(100)    NOT NULL,
  `owner_phone`   VARCHAR(20)     NOT NULL,
  `owner_email`   VARCHAR(100)    DEFAULT NULL,
  `vehicle_desc`  VARCHAR(150)    DEFAULT NULL,
  `created_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plate` (`license_plate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATI E=utf8mb4_unicode_ci;

-- Sample records (3 max) — replace with real data
INSERT INTO `wp_sjioc_vehicles`
  (`license_plate`, `owner_name`, `owner_phone`, `owner_email`, `vehicle_desc`)
VALUES
  ('JKL 4521', 'Thomas Abraham', '(610) 555-0182', 'thomas.abraham@example.com', 'Blue Toyota Camry'),
  ('MNP 7834', 'Susan Mathew',   '(610) 555-0247', 'susan.mathew@example.com',   'Silver Honda CR-V'),
  ('QRS 2916', 'George Philip',  '(610) 555-0391', 'george.philip@example.com',  'White Hyundai Sonata');