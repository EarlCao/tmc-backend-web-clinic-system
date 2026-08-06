<?php

namespace App\Http\Controllers;

use App\Http\Resources\StaffResource;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    /**
     * List the staff roster for today's duty schedule.
     */
    public function index(): AnonymousResourceCollection
    {
        return StaffResource::collection(Staff::orderBy('name')->get());
    }

    /**
     * Update a staff member's duty status (On duty / Break / Off duty).
     * The existing frontend keys staff by name, so the body carries the name.
     */
    public function updateStatus(Request $request): StaffResource|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'exists:staff,name'],
            'status' => ['required', Rule::in(Staff::STATUSES)],
        ]);

        $staff = Staff::where('name', $validated['name'])->firstOrFail();
        $staff->update(['status' => $validated['status']]);

        return new StaffResource($staff);
    }
}
