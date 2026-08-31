<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Startup;
use App\Services\VentureExitAiGenerator;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class VentureExitAiController extends Controller
{
    /**
     * "Generate with AI" on the Venture Exit document (Document 13) — never
     * saves anything itself. Returns the drafted fields as JSON for the
     * page's own Alpine state to merge in, so the admin can review/edit
     * before the normal Save Assessment button persists it.
     */
    public function generate(Startup $startup, VentureExitAiGenerator $generator): JsonResponse
    {
        try {
            $data = $generator->generate($startup);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $data]);
    }
}
