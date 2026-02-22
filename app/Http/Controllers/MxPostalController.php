<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MxPostalController extends Controller
{
    public function lookup(string $cp)
    {
        // ✅ Validación básica: 5 dígitos
        if (!preg_match('/^\d{5}$/', $cp)) {
            return response()->json([
                'ok' => false,
                'message' => 'CP inválido (deben ser 5 dígitos)',
            ], 422);
        }

        $apikey = config('services.dipomex.apikey');

        if (!$apikey) {
            return response()->json([
                'ok' => false,
                'message' => 'APIKEY de DIPOMEX no configurada',
            ], 500);
        }

        // ✅ Cache (7 días)
        $cacheKey = "dipomex:cp:{$cp}";
        $cached = Cache::get($cacheKey);

        // Si ya tenemos cache, lo podemos devolver al instante (opcional).
        // Si prefieres siempre “refrescar” con la API, comenta este bloque.
        if ($cached) {
            return response()->json($cached);
        }

        try {
            $res = Http::connectTimeout(20)   // ⬅️ corta rápido si no conecta
                ->timeout(50)                // ⬅️ no esperes 10+ segundos
                ->retry(1, 200)             // ⬅️ 1 reintento corto
                ->withHeaders(['APIKEY' => $apikey])
                ->get('https://api.tau.com.mx/dipomex/v1/codigo_postal', [
                    'cp' => $cp,
                ]);

            if (!$res->ok()) {
                Log::warning('DIPOMEX cp lookup failed', [
                    'cp' => $cp,
                    'status' => $res->status(),
                    'body' => $res->body(),
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'Error consultando CP',
                ], 502);
            }

            $json = $res->json();

            if (($json['error'] ?? true) === true || empty($json['codigo_postal'])) {
                return response()->json([
                    'ok' => false,
                    'message' => 'CP no encontrado',
                ], 404);
            }

            $cpInfo = $json['codigo_postal'];

            // 1) Colonias "directas" por CP
            $rawColonias = $cpInfo['colonias'] ?? [];

            $colonies = collect($rawColonias)
                ->map(function ($x) {
                    // soporta array de objetos { colonia: "..." }
                    if (is_array($x) && isset($x['colonia']))
                        return $x['colonia'];
                    // soporta por si viniera como string directo
                    if (is_string($x))
                        return $x;
                    return null;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            // 2) Fallback: si viene vacío, consultamos colonias por estado/municipio y filtramos por CP
            if (empty($colonies) && !empty($cpInfo['estado_id']) && !empty($cpInfo['municipio_id'])) {
                try {
                    $res2 = Http::connectTimeout(2)
                        ->timeout(5)
                        ->retry(1, 200)
                        ->withHeaders(['APIKEY' => $apikey])
                        ->get('https://api.tau.com.mx/dipomex/v1/colonias', [
                            'id_estado' => $cpInfo['estado_id'],
                            'id_mun' => $cpInfo['municipio_id'],
                        ]);

                    if ($res2->ok()) {
                        $json2 = $res2->json();

                        if (($json2['error'] ?? true) === false && !empty($json2['colonias'])) {
                            $colonies = collect($json2['colonias'])
                                // en este endpoint la key suele venir en mayúsculas: CP y COLONIA
                                ->filter(fn($c) => (string) ($c['CP'] ?? '') === (string) $cp)
                                ->pluck('COLONIA')
                                ->filter()
                                ->unique()
                                ->values()
                                ->all();
                        }
                    }
                } catch (\Throwable $e) {
                    // Fallback no debe romper la respuesta principal
                    Log::warning('DIPOMEX colonias fallback exception', [
                        'cp' => $cp,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $payload = [
                'ok' => true,
                'data' => [
                    'cp' => $cpInfo['codigo_postal'] ?? $cp,
                    'state' => $cpInfo['estado'] ?? '',
                    'state_abbr' => $cpInfo['estado_abreviatura'] ?? '',
                    'municipality' => $cpInfo['municipio'] ?? '',
                    'colonies' => $colonies,
                ],
            ];

            // ✅ Cache por 7 días
            Cache::put($cacheKey, $payload, now()->addDays(7));

            return response()->json($payload);

        } catch (\Throwable $e) {
            // ✅ Si por alguna razón llega a fallar la API, y tuvieras cache, lo devolvemos.
            // (Aquí cached sería null porque arriba hicimos early return, pero lo dejo por si luego cambias ese comportamiento.)
            if ($cached) {
                return response()->json($cached);
            }

            Log::error('DIPOMEX cp lookup exception', [
                'cp' => $cp,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Servicio de CP no disponible (timeout)',
            ], 503);
        }
    }
}
