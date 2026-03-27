<?php

declare(strict_types=1);

namespace App\Application\Service;

class WhatsAppService
{
    private const WHATSAPP_BASE_URL = 'https://wa.me/';
    private const DEFAULT_COUNTRY_CODE = '55';

    /**
     * Sanitiza número de telefone, removendo caracteres não numéricos.
     */
    public function sanitizePhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // Se não começar com código do país, adiciona 55 (Brasil)
        if (strlen($clean) <= 11) {
            $clean = self::DEFAULT_COUNTRY_CODE . $clean;
        }

        return $clean;
    }

    /**
     * Gera link dinâmico do WhatsApp com mensagem pré-formatada.
     */
    public function generateLink(string $phone, array $bookingData): string
    {
        $sanitized = $this->sanitizePhone($phone);

        $message = $this->buildMessage($bookingData);
        $encoded = rawurlencode($message);

        return self::WHATSAPP_BASE_URL . $sanitized . '?text=' . $encoded;
    }

    /**
     * Monta mensagem de conversão para WhatsApp.
     */
    private function buildMessage(array $data): string
    {
        $lines = [
            '🚗 *Novo Agendamento - Motorista Particular*',
            '',
            '👤 *Nome:* ' . ($data['nome'] ?? 'Não informado'),
            '📍 *Origem:* ' . ($data['origem'] ?? ''),
            '📍 *Destino:* ' . ($data['destino'] ?? ''),
            '📅 *Data/Hora:* ' . ($data['data_hora'] ?? ''),
            '🚘 *Veículo:* ' . ($data['veiculo'] ?? ''),
        ];

        if (isset($data['distancia_km'])) {
            $lines[] = '📏 *Distância:* ' . $data['distancia_km'] . ' km';
        }

        if (isset($data['valor_estimado'])) {
            $lines[] = '💰 *Valor Estimado:* R$ ' . number_format((float) $data['valor_estimado'], 2, ',', '.');
        }

        $lines[] = '';
        $lines[] = '_Aguardo confirmação!_ ✅';

        return implode("\n", $lines);
    }
}
