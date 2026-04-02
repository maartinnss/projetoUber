-- ===========================================
-- Migration: Adicionar campos de tipo, capacidade e imagem
-- ===========================================

ALTER TABLE `configuracoes_veiculo`
    ADD COLUMN `tipo` ENUM('sedan', 'suv', 'van') NOT NULL DEFAULT 'sedan' AFTER `modelo`,
    ADD COLUMN `capacidade_passageiros` INT NOT NULL DEFAULT 4 AFTER `tipo`,
    ADD COLUMN `imagem_url` VARCHAR(500) DEFAULT NULL AFTER `capacidade_passageiros`;

-- Atualiza o veículo já existente
UPDATE `configuracoes_veiculo` SET `tipo` = 'sedan', `capacidade_passageiros` = 4 WHERE `id` = 1;
