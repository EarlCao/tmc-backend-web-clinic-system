<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * List all roles with permission and user counts.
     */
    public function index(): AnonymousResourceCollection
    {
        $roles = Role::withCount('users')
            ->withCount('permissions')
            ->orderBy('name')
            ->get();

        return RoleResource::collection($roles);
    }

    /**
     * Create a new role, optionally with its initial permissions.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = DB::transaction(function () use ($request) {
            $role = Role::create($request->safe(['name', 'description']));
            $role->permissions()->sync($request->validated('permissions', []));

            return $role;
        });

        return (new RoleResource($role->load('permissions')->loadCount('users')->loadCount('permissions')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single role with its full permission list.
     */
    public function show(Role $role): RoleResource
    {
        return new RoleResource(
            $role->load('permissions')->loadCount('users')->loadCount('permissions'),
        );
    }

    /**
     * Update role information and, when provided, its assigned permissions.
     */
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $role = DB::transaction(function () use ($request, $role) {
            $role->update($request->safe(['name', 'description']));

            if ($request->has('permissions')) {
                $role->permissions()->sync($request->validated('permissions'));
            }

            return $role;
        });

        return new RoleResource(
            $role->load('permissions')->loadCount('users')->loadCount('permissions'),
        );
    }

    /**
     * Delete a role, subject to system restrictions.
     *
     * System roles that keep the authorization structure intact cannot be
     * deleted, nor can roles that still have users assigned.
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json(['message' => 'System roles cannot be deleted.'], 409);
        }

        if ($role->users()->exists()) {
            return response()->json(['message' => 'This role is assigned to users and cannot be deleted.'], 409);
        }

        DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * Replace the permissions assigned to a role.
     */
    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role): RoleResource
    {
        $role->permissions()->sync($request->validated('permissions'));

        return new RoleResource(
            $role->load('permissions')->loadCount('users')->loadCount('permissions'),
        );
    }
}
