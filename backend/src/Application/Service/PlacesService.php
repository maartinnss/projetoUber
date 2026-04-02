<?php

declare(strict_types=1);

namespace App\Application\Service;

class PlacesService
{
    /**
     * Busca endereços e sanitiza as duplicatas da base oficial do OpenStreetMap
     * usando a Photon API (komoot), otimizada e livre.
     */
    public function search(string $query): array
    {
        // Adiciona "Brasil" implicitamente para afunilar a busca.
        $encodedQuery = urlencode($query . ", Brasil");
        $url = "https://photon.komoot.io/api/?q={$encodedQuery}&limit=15";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DriverEliteApp/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout de 5 segundos para não travar o PHP
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($response === false || $httpCode !== 200) {
            error_log("PlacesService Error: [HTTP $httpCode] " . ($error ?: "Falha na resposta da Photon API"));
            return [];
        }

        $data = json_decode($response, true);
        if (empty($data['features'])) {
            return [];
        }

        $results = [];
        $fingerprints = []; // Controle de duplicatas

        foreach ($data['features'] as $feature) {
            $props = $feature['properties'];
            $coords = $feature['geometry']['coordinates'] ?? null; // [lon, lat]

            if (!$coords) continue;

            $name = $props['name'] ?? '';
            $street = $props['street'] ?? $props['pedestrian'] ?? '';
            $city = $props['city'] ?? $props['town'] ?? $props['village'] ?? '';
            $state = $props['state'] ?? '';

            // Monta algo bonito e legível para o usuário final
            $displayNameParts = [];
            
            if (!empty($name) && $name !== $street && $name !== $city) {
                $displayNameParts[] = $name;
            }
            if (!empty($street)) {
                $displayNameParts[] = $street;
            }
            if (!empty($city)) {
                $displayNameParts[] = $city;
            }
            if (!empty($state)) {
                $displayNameParts[] = $state;
            }

            if (empty($displayNameParts)) {
                continue;
            }

            $displayName = implode(', ', $displayNameParts);

            // Evitar exibir "Brasil" poluidamente toda vez
            $displayName = str_replace([', Brazil', ', Brasil'], '', $displayName);

            // Fingerprint único combinando o nome do endereço exibido
            $fingerprint = md5(mb_strtolower($displayName));

            if (!isset($fingerprints[$fingerprint])) {
                $fingerprints[$fingerprint] = true;
                
                $results[] = [
                    'display_name' => $displayName,
                    'lat' => $coords[1],
                    'lon' => $coords[0],
                ];

                if (count($results) >= 5) {
                    break; // Retorna no máximo 5 opções excelentes
                }
            }
        }

        return $results;
    }
}
