<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index()
    {
        return response()->json($this->userService->getAll());
    }

    public function show($id)
    {
        return response()->json($this->userService->getById($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'nullable|exists:roles,id'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role_id' => $validated['role_id'] ?? null,
        ]);

        $user->load('role');
        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'lastname' => 'sometimes|string',
            'email' => "sometimes|email|unique:users,email,{$id}",
            'password' => 'nullable|string|min:6',
            'role_id' => 'nullable|exists:roles,id'
        ]);

        $user = $this->userService->updateUser($id, $validated);
        $user->load('role');
        return response()->json($user);
    }

    public function destroy($id)
    {
        $this->userService->delete($id);
        return response()->json(null, 204);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load('role');
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'role' => [
                'id' => $user->role->id,
                'name' => $user->role->name
            ]
        ]);
    }
}