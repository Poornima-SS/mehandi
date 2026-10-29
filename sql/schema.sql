CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS artists (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  city VARCHAR(80) NOT NULL,
  experience_years INT UNSIGNED NOT NULL,
  specialty VARCHAR(120) NOT NULL,
  bio TEXT NOT NULL,
  rating DECIMAL(2,1) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  initials VARCHAR(4) NOT NULL,
  accent CHAR(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS designs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  artist_id INT UNSIGNED NOT NULL,
  title VARCHAR(120) NOT NULL,
  category VARCHAR(60) NOT NULL,
  price INT UNSIGNED NOT NULL,
  duration_hours DECIMAL(3,1) NOT NULL,
  description TEXT NOT NULL,
  INDEX idx_designs_artist (artist_id),
  INDEX idx_designs_category_price (category, price),
  CONSTRAINT fk_designs_artist FOREIGN KEY (artist_id) REFERENCES artists (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  artist_id INT UNSIGNED NOT NULL,
  design_id INT UNSIGNED NOT NULL,
  event_date DATE NOT NULL,
  occasion VARCHAR(40) NOT NULL,
  amount INT UNSIGNED NOT NULL,
  notes TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bookings_user (user_id),
  CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users (id),
  CONSTRAINT fk_bookings_artist FOREIGN KEY (artist_id) REFERENCES artists (id),
  CONSTRAINT fk_bookings_design FOREIGN KEY (design_id) REFERENCES designs (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
