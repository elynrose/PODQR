<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        Log::info('Admin access attempt', [
            'user_id' => $user->id,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
            'can_admin' => Gate::allows('admin', $user)
        ]);

        if (! Gate::allows('admin')) {
            Log::warning('Unauthorized admin access attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'is_admin' => $user->is_admin
            ]);
            abort(403, 'Unauthorized access to admin area.');
        }

        $users = User::all();
        
        return view('admin.dashboard', [
            'users' => $users
        ]);
    }

    /**
     * Display the user management page.
     */
    public function users(Request $request): View
    {
        if (!Gate::allows('admin')) {
            abort(403, 'Unauthorized access to admin area.');
        }

        $search = $request->get('search');
        $users = User::when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.users.index', compact('users', 'search'));
    }

    /**
     * Show user details.
     */
    public function showUser(User $user): View
    {
        if (!Gate::allows('admin')) {
            abort(403, 'Unauthorized access to admin area.');
        }

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the user.
     */
    public function editUser(User $user): View
    {
        if (!Gate::allows('admin')) {
            abort(403, 'Unauthorized access to admin area.');
        }

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the user.
     */
    public function updateUser(Request $request, User $user)
    {
        if (!Gate::allows('admin')) {
            abort(403, 'Unauthorized access to admin area.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'is_admin' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Only allow changing admin status if the current user is not the one being edited
        if ($user->id === $request->user()->id) {
            unset($validated['is_admin']);
        }

        // Only update password if it was provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Ban a user.
     */
    public function banUser(User $user)
    {
        if (!Gate::allows('admin')) {
            abort(403, 'Unauthorized access to admin area.');
        }

        if ($user->isAdmin()) {
            return back()->with('error', 'Cannot ban an admin user.');
        }

        $user->ban();

        return back()->with('success', 'User has been banned.');
    }

    /**
     * Unban a user.
     */
    public function unbanUser(User $user)
    {
        if (!Gate::allows('admin')) {
            abort(403, 'Unauthorized access to admin area.');
        }

        $user->unban();

        return back()->with('success', 'User has been unbanned.');
    }

    /**
     * Delete a user.
     */
    public function destroyUser(User $user)
    {
        if (!Gate::allows('admin')) {
            abort(403, 'Unauthorized access to admin area.');
        }

        if ($user->isAdmin()) {
            return back()->with('error', 'Cannot delete an admin user.');
        }

        $user->delete();

        return back()->with('success', 'User has been deleted.');
    }
} 