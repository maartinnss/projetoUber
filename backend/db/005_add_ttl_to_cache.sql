-- Adicionar TTL (Time To Live) aos caches geográficos e de rotas
ALTER TABLE geo_cache ADD COLUMN expires_at TIMESTAMP NULL AFTER lon;
ALTER TABLE route_cache ADD COLUMN expires_at TIMESTAMP NULL AFTER distance_km;

-- Definir expiração padrão para 30 dias para registros existentes
UPDATE geo_cache SET expires_at = DATE_ADD(created_at, INTERVAL 30 DAY) WHERE expires_at IS NULL;
UPDATE route_cache SET expires_at = DATE_ADD(created_at, INTERVAL 30 DAY) WHERE expires_at IS NULL;
