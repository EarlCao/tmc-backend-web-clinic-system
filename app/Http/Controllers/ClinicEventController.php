<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClinicEventResource;
use App\Models\ClinicEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClinicEventController extends Controller
{
    /**
     * List campus health events, newest created first.
     *
     * `date` is a display string (e.g. "Aug 03, 2026"), so it cannot be used
     * for reliable chronological ordering; id order matches insertion order.
     */
    public function index(): AnonymousResourceCollection
    {
        return ClinicEventResource::collection(ClinicEvent::orderByDesc('id')->get());
    }

    /**
     * Schedule a new campus health event.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $event = ClinicEvent::create([
            'date' => $validated['date'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
        ]);

        return (new ClinicEventResource($event))->response()->setStatusCode(201);
    }
}
