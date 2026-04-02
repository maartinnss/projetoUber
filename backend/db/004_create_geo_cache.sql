CREATE TABLE IF NOT EXISTS geo_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    address_query VARCHAR(255) NOT NULL,
    lat DECIMAL(10, 8) NOT NULL,
    lon DECIMAL(11, 8) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (address_query)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS route_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin_query VARCHAR(255) NOT NULL,
    destination_query VARCHAR(255) NOT NULL,
    distance_km DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (origin_query, destination_query)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
