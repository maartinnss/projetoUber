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
        // Adiciona "Brasil" implicitamente para afunilar a busca, ou usa bbox no futuro.
        $encodedQuery = urlencode($query . ", Brasil");
        $url = "https://photon.komoot.io/api/?q={$encodedQuery}&limit=15";

        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: DriverEliteApp/1.0\r\n"
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        
        if (!$response) {
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
            $displayName = str_replace(', Brazil', '', $displayName);
            $displayName = str_replace(', Brasil', '', $displayName);

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
