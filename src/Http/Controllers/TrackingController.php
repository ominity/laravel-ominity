<?php

namespace Ominity\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ominity\Laravel\Services\OminityTrackingService;

class TrackingController extends Controller
{
    public function __construct(protected OminityTrackingService $tracking) {}

    public function event(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string'],
            'timestamp' => ['nullable'],
            'title' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'visitorId' => ['nullable', 'uuid'],
            'userId' => ['nullable', 'integer'],
            'referrer' => ['nullable', 'string'],
            'utm' => ['nullable', 'array'],
        ]);

        try {
            return response()->json($this->tracking->track($validated, $request));
        } catch (\Throwable $throwable) {
            report($throwable);

            return response()->json([
                'success' => false,
                'error' => $throwable->getMessage(),
            ], 500);
        }
    }
}
