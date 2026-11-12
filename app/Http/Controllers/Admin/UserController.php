<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/**
 * Staff accounts (replaces creating users through tinker).
 *
 * Staff are always bound to exactly one invitation; the owner keeps the
 * `admin` role and is managed outside this screen.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with('invitation:id,title,slug')
            ->where('role', '!=', User::ROLE_ADMIN)
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
                'invitation_id' => $user->invitation_id,
                'invitation' => $user->invitation?->title,
            ]);

        return Inertia::render('Admin/UserIndex', [
            'users' => $users,
            'invitations' => Invitation::query()
                ->orderBy('title')
                ->get(['id', 'title', 'slug']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'invitation_id' => ['required', 'integer', 'exists:invitations,id'],
            'role' => ['required', Rule::in([User::ROLE_STAFF, User::ROLE_CLIENT])],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'invitation_id' => $validated['invitation_id'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Akun petugas berhasil dibuat.');
    }

    public function update(Request $request, int $id)
    {
        $user = $this->staffOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'invitation_id' => ['required', 'integer', 'exists:invitations,id'],
            'role' => ['required', Rule::in([User::ROLE_STAFF, User::ROLE_CLIENT])],
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Akun petugas berhasil diperbarui.');
    }

    public function resetPassword(Request $request, int $id)
    {
        $user = $this->staffOrFail($id);

        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Password petugas berhasil direset.');
    }

    private function staffOrFail(int $id): User
    {
        $user = User::findOrFail($id);

        // Never let this screen demote or hijack the owner account.
        abort_if($user->isAdmin(), 403);

        return $user;
    }
}
