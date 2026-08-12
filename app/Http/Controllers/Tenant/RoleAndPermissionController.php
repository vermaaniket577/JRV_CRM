<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RoleAndPermissionController extends Controller
{
    /**
     * Display custom tenant roles and grouped section permissions.
     */
    public function index(): Response
    {
        $roles = Role::with(['permissions', 'users'])->get();
        $permissions = Permission::all();

        // Group permissions by section for clear UI checklist organization
        $groupedPermissions = $permissions->groupBy('section')->map(function ($sectionItems, $sectionName) {
            return [
                'section' => $sectionName,
                'items' => PermissionResource::collection($sectionItems),
            ];
        })->values();

        return Inertia::render('Tenant/Settings/Roles/Index', [
            'roles' => RoleResource::collection($roles),
            'permissionSections' => $groupedPermissions,
        ]);
    }

    /**
     * Store or update custom tenant role with section permission checkmarks.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $role = Role::create([
                'name' => $request->validated('name'),
                'guard_name' => 'web',
            ]);

            $role->permissions()->sync($request->validated('permissions'));
        });

        return redirect()->back()->with('success', 'Custom role created successfully with section permissions.');
    }

    /**
     * Update permissions for an existing custom role.
     */
    public function update(StoreRoleRequest $request, Role $role): RedirectResponse
    {
        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->validated('name')]);
            $role->permissions()->sync($request->validated('permissions'));
        });

        return redirect()->back()->with('success', 'Role section permissions updated successfully.');
    }

    /**
     * Delete a custom role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->count() > 0) {
            return redirect()->back()->withErrors(['role' => 'Cannot delete role assigned to active staff members.']);
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }
}
