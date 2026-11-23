<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Settings', [
            'settings' => Setting::business(),
            'cashiers' => User::query()
                ->where('role', User::ROLE_CASHIER)
                ->withCount('sales')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'created_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'sales_count' => $user->sales_count,
                    'created_at' => $user->created_at->toIso8601String(),
                ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::put($key, $value);
        }

        return back()->with('success', 'Store settings saved.');
    }

    public function storeCashier(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => User::ROLE_CASHIER,
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        return back()->with('success', "Cashier account for {$user->name} created.");
    }

    public function destroyCashier(User $user): RedirectResponse
    {
        if (! $user->isCashier()) {
            return back()->with('error', 'Only cashier accounts can be removed from this screen.');
        }

        if ($user->sales()->exists()) {
            return back()->with('error', 'This cashier has sales history and cannot be deleted.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Cashier {$name} removed.");
    }
}
