<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Exercise;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AIRoutineController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|file|image|max:8192',
        ]);

        $imageData   = base64_encode(file_get_contents($request->file('photo')->getRealPath()));
        $mediaType   = $request->file('photo')->getMimeType();

        $response = Http::withHeaders([
            'x-api-key'         => env('ANTHROPIC_API_KEY'),
            'anthropic-version' => '2023-06-01',
            'Content-Type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 2048,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'  => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mediaType,
                                'data'       => $imageData,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Analizá esta imagen de entrenamiento y generá una rutina de ejercicios estructurada. '
                                . 'Respondé ÚNICAMENTE con un JSON válido, sin markdown, sin explicaciones, sin bloques de código. '
                                . 'El JSON debe tener exactamente esta estructura: '
                                . '{"name":"Nombre de la rutina","description":"Descripción breve","days":[{"day_number":1,"name":"Nombre del día","blocks":[{"name":"Nombre del bloque","order":1,"exercises":[{"name":"Nombre del ejercicio","sets":3,"reps":"10","rest_seconds":60,"notes":""}]}]}]}. '
                                . 'Identificá todos los ejercicios visibles en la imagen, sus series, repeticiones y descansos aproximados. '
                                . 'Si no podés determinar un valor numérico, usá valores típicos para ese tipo de ejercicio.',
                        ],
                    ],
                ],
            ],
        ]);

        $body    = $response->json();
        $jsonStr = $body['content'][0]['text'] ?? '';

        // Strip any accidental markdown fences
        $jsonStr = preg_replace('/^```(?:json)?\s*/i', '', trim($jsonStr));
        $jsonStr = preg_replace('/\s*```$/', '', $jsonStr);

        $parsed = json_decode(trim($jsonStr), true);

        abort_if($parsed === null, 422, 'La IA no devolvió un JSON válido.');

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
}
