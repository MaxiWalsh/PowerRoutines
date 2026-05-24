<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Exercise;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIRoutineController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|file|image|max:8192',
        ]);

        $imageData = base64_encode($this->resizeImage($request->file('photo')->getRealPath(), 1600));
        $mediaType = 'image/jpeg';

        $http = Http::timeout(30);
        if (app()->isLocal()) {
            $http = $http->withoutVerifying();
        }

        $prompt = 'Esta imagen contiene una rutina de entrenamiento escrita (puede ser papel, pizarrón, captura de pantalla o similar). '
            . 'Leé todo el texto de la imagen y transcribí la rutina al siguiente formato JSON. '
            . 'Respondé ÚNICAMENTE con el JSON, sin explicaciones ni markdown. '
            . 'Estructura exacta requerida: '
            . '{"name":"Nombre de la rutina","description":"Descripción breve","days":[{"day_number":1,"name":"Nombre del día","blocks":[{"name":"Nombre del bloque","order":1,"exercises":[{"name":"Nombre del ejercicio","sets":3,"reps":"10","rest_seconds":60,"notes":""}]}]}]}. '
            . 'Reglas: '
            . '1. Transcribí los ejercicios exactamente como están escritos en la imagen. '
            . '2. Si hay días o grupos (ej: "Día A", "Tren superior"), usalos como days/blocks. Si no hay división por días, usá un solo día. '
            . '3. Para sets/reps/rest que no estén escritos, usá valores estándar (sets:3, reps:"10", rest_seconds:60). '
            . '4. El campo reps puede ser string para rangos o texto (ej: "8-12", "al fallo"). '
            . '5. Si la imagen no contiene una rutina de entrenamiento, devolvé {"error":"no_routine"}.';

        $response = $http
            ->withHeaders([
                'x-api-key'         => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 2048,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type'   => 'image',
                                'source' => [
                                    'type'       => 'base64',
                                    'media_type' => $mediaType,
                                    'data'       => $imageData,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            abort(503, 'Hubo un problema al analizar la imagen. Intentá de nuevo en unos momentos.');
        }

        $body    = $response->json();
        $jsonStr = $body['content'][0]['text'] ?? '';

        // Strip any accidental markdown fences
        $jsonStr = preg_replace('/^```(?:json)?\s*/i', '', trim($jsonStr));
        $jsonStr = preg_replace('/\s*```$/',           '', $jsonStr);

        $parsed = json_decode(trim($jsonStr), true);

        if ($parsed === null) {
            Log::warning('Gemini JSON parse failed', ['raw' => $jsonStr]);
            abort(422, 'No pudimos generar la rutina a partir de esta imagen. Intentá con una foto más clara del entrenamiento.');
        }

        if (isset($parsed['error'])) {
            abort(422, 'La imagen no parece contener una rutina de entrenamiento. Usá una foto de una rutina escrita.');
        }

        $user = $request->user();

        $routine = DB::transaction(function () use ($parsed, $user) {
            $routine = Routine::create([
                'owner_id'    => $user->id,
                'name'        => $parsed['name']        ?? 'Rutina desde foto',
                'description' => $parsed['description'] ?? null,
                'scope'       => 'personal',
                'is_template' => false,
            ]);

            foreach ($parsed['days'] ?? [] as $dayIndex => $dayData) {
                // Create the day block (top-level, no parent_id)
                $day = Block::create([
                    'routine_id' => $routine->id,
                    'parent_id'  => null,
                    'name'       => $dayData['name'] ?? "Día " . ($dayIndex + 1),
                    'order'      => $dayData['day_number'] ?? ($dayIndex + 1),
                ]);

                foreach ($dayData['blocks'] ?? [] as $blockIndex => $blockData) {
                    // Create the section block (child of day)
                    $section = Block::create([
                        'routine_id' => $routine->id,
                        'parent_id'  => $day->id,
                        'name'       => $blockData['name'] ?? "Bloque " . ($blockIndex + 1),
                        'order'      => $blockData['order'] ?? ($blockIndex + 1),
                    ]);

                    foreach ($blockData['exercises'] ?? [] as $exIndex => $exData) {
                        $exercise = Exercise::firstOrCreate(
                            [
                                'name'       => $exData['name'],
                                'created_by' => $user->id,
                            ],
                            [
                                'is_global' => false,
                            ]
                        );

                        $reps = is_numeric($exData['reps'] ?? null)
                            ? (int) $exData['reps']
                            : null;

                        $section->exercises()->attach($exercise->id, [
                            'sets'     => $exData['sets']         ?? 3,
                            'reps'     => $reps,
                            'rest_sec' => $exData['rest_seconds'] ?? 60,
                            'order'    => $exIndex + 1,
                            'notes'    => $exData['notes']        ?? null,
                        ]);
                    }
                }
            }

            return $routine;
        });

        return response()->json(['routine_id' => $routine->id], 201);
    }

    private function resizeImage(string $path, int $maxDimension): string
    {
        if (! extension_loaded('gd')) {
            return file_get_contents($path);
        }

        [$width, $height, $type] = getimagesize($path);

        if ($width <= $maxDimension && $height <= $maxDimension) {
            return file_get_contents($path);
        }

        $ratio   = min($maxDimension / $width, $maxDimension / $height);
        $newW    = (int) round($width * $ratio);
        $newH    = (int) round($height * $ratio);

        $src = match ($type) {
            IMAGETYPE_JPEG => \imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => \imagecreatefrompng($path),
            IMAGETYPE_WEBP => \imagecreatefromwebp($path),
            default        => null,
        };

        if ($src === null) {
            return file_get_contents($path);
        }

        $dst = \imagecreatetruecolor($newW, $newH);
        \imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        \imagedestroy($src);

        ob_start();
        \imagejpeg($dst, null, 85);
        \imagedestroy($dst);

        return ob_get_clean();
    }
}
