<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    // ─── Users ───────────────────────────────────────────────────────────────

    public function users(Request $request)
    {
        $users = User::with('roles', 'permissions')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $users->map(fn($u) => $this->formatUser($u)),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    public function showUser(int $id)
    {
        $user = $this->roleService->getUserById($id);
        return response()->json(['data' => $this->formatUser($user)]);
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|min:2|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|string|exists:roles,name',
        ]);

        $user = $this->roleService->createUser([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role_id' => Role::where('name', $data['role'])->first()->id,
        ]);

        return response()->json(['data' => $this->formatUser($user)], 201);
    }

    public function updateUser(Request $request, int $id)
    {
        $data = $request->validate([
            'name'     => 'sometimes|required|string|min:2|max:255',
            'email'    => "sometimes|required|email|unique:users,email,{$id}",
            'password' => 'nullable|string|min:6',
            'role'     => 'sometimes|required|string|exists:roles,name',
        ]);

        if (isset($data['role'])) {
            $data['role_id'] = Role::where('name', $data['role'])->first()->id;
        }

        $user = $this->roleService->updateUser($id, $data);
        return response()->json(['data' => $this->formatUser($user)]);
    }

    public function destroyUser(int $id)
    {
        $user = User::findOrFail($id);
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 422);
        }
        $this->roleService->deleteUser($id);
        return response()->json(null, 204);
    }

    // ─── Roles ────────────────────────────────────────────────────────────────

    public function index()
    {
        return response()->json(['data' => $this->roleService->getAllRoles()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = $this->roleService->createRole($data);
        return response()->json(['data' => $role], 201);
    }

    public function show(string $id)
    {
        return response()->json(['data' => $this->roleService->getRoleById($id)]);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name'        => "sometimes|required|string|unique:roles,name,{$id}",
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = $this->roleService->updateRole($id, $data);
        return response()->json(['data' => $role]);
    }

    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        // Protect built-in roles
        if (in_array($role->name, ['admin', 'cashier', 'kitchen'])) {
            return response()->json(['message' => 'Cannot delete built-in roles'], 422);
        }
        $this->roleService->deleteRole($id);
        return response()->json(null, 204);
    }

    public function permissions()
    {
        return response()->json(['data' => $this->roleService->getAllPermissions()]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function formatUser(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'created_at'  => $user->created_at?->toDateTimeString(),
        ];
    }
}
