<?php

namespace App\Http\Controllers;

use App\Services\Copilot\CopilotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CopilotController extends Controller
{
    public function __construct(private readonly CopilotService $copilot)
    {
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json($this->copilot->history($request->user()));
    }

    public function reset(Request $request): JsonResponse
    {
        return response()->json($this->copilot->reset($request->user()));
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'string', 'max:80'],
        ]);

        return response()->json(
            $this->copilot->chat(
                $request->user(),
                trim((string) $validated['message']),
                $validated['conversation_id'] ?? null,
            )
        );
    }
}
