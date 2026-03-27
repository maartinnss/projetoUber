-- ===========================================
-- Esquema: Motorista Particular App
-- ===========================================

CREATE TABLE IF NOT EXISTS `configuracoes_veiculo` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `modelo` VARCHAR(100) NOT NULL,
    `preco_por_km` DECIMAL(10,2) NOT NULL,
    `descricao` VARCHAR(255) DEFAULT NULL,
    `ativo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `agendamentos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `whatsapp` VARCHAR(20) NOT NULL,
    `origem` VARCHAR(255) NOT NULL,
    `destino` VARCHAR(255) NOT NULL,
    `data_hora` DATETIME NOT NULL,
    `veiculo_id` INT DEFAULT NULL,
    `distancia_km` DECIMAL(10,2) DEFAULT NULL,
    `valor_estimado` DECIMAL(10,2) DEFAULT NULL,
    `status` ENUM('pendente','confirmado','em_andamento','concluido','cancelado') DEFAULT 'pendente',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_agendamento_veiculo` FOREIGN KEY (`veiculo_id`) REFERENCES `configuracoes_veiculo`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
