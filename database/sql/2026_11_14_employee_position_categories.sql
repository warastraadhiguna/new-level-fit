-- Select the FACE database for this installation: level-fit, fitbull, or gelora-gym.
-- Run this SQL OR the new Master migration. Existing employees.position_category is retained.
CREATE TABLE IF NOT EXISTS employee_position_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY employee_position_categories_code_unique (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO employee_position_categories
  (code, name, is_active, created_at, updated_at)
VALUES
  ('PT', 'PT', 1, NOW(), NOW()),
  ('CS', 'CS', 1, NOW(), NOW()),
  ('HR', 'HR', 1, NOW(), NOW()),
  ('FINANCE', 'Finance', 1, NOW(), NOW());

-- Include any legacy categories; retain names/status of categories already managed.
INSERT IGNORE INTO employee_position_categories
  (code, name, is_active, created_at, updated_at)
SELECT DISTINCT e.position_category, e.position_category, 1, NOW(), NOW()
FROM employees AS e
WHERE e.position_category IS NOT NULL
  AND e.position_category <> '';
