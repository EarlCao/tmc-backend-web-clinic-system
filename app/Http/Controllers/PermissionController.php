<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PermissionController extends Controller
{
    /**
     * List every permission in the system.
     *
     * The frontend groups them by `module` to build the assignment UI.
     */
    public function index(): AnonymousResourceCollection
    {
        return PermissionResource::collection(
            Permission::orderBy('module')->orderBy('name')->get(),
        );
    }
}
