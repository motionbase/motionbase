<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        // Check if current user is admin
        abort_unless(auth()->user()?->is_admin, 403);

        $users = User::query()
            ->withCount('topics')
            ->latest()
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'topics_count' => $user->topics_count,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('users/index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        abort_unless(auth()->user()?->is_admin, 403);

        return Inertia::render('users/create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'is_admin' => ['boolean'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()
            ->route('users.index')
            ->with('flash', ['status' => 'success', 'message' => 'Benutzer erfolgreich erstellt.']);
    }

    public function edit(User $user): Response
    {
        abort_unless(auth()->user()?->is_admin, 403);

        return Inertia::render('users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->is_admin, 403);

        // Prevent user from removing their own admin status
        if ($user->id === auth()->id() && ! $request->boolean('is_admin')) {
            return back()->withErrors(['is_admin' => 'Du kannst dir nicht selbst die Admin-Rechte entziehen.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'is_admin' => ['boolean'],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('users.index')
            ->with('flash', ['status' => 'success', 'message' => 'Benutzer aktualisiert.']);
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->is_admin, 403);

        // Prevent user from deleting themselves
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Du kannst dich nicht selbst löschen.']);
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('flash', ['status' => 'success', 'message' => 'Benutzer gelöscht.']);
    }
}
