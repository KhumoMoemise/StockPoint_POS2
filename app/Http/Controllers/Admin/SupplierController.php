<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Supplier::query()->withCount(['products', 'purchases']);

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->get()->map(fn (Supplier $supplier) => [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'contact_person' => $supplier->contact_person,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'address' => $supplier->address,
            'products_count' => $supplier->products_count,
            'purchases_count' => $supplier->purchases_count,
            'can_delete' => $supplier->products_count === 0 && $supplier->purchases_count === 0,
        ]);

        return Inertia::render('Admin/Suppliers', [
            'suppliers' => $suppliers,
            'filters' => ['q' => $request->input('q', '')],
            'openCreate' => $request->boolean('create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSupplier($request);

        $supplier = Supplier::create($validated);

        return back()->with('success', "Supplier {$supplier->name} added.");
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validateSupplier($request));

        return back()->with('success', "Supplier {$supplier->name} updated.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->products()->exists() || $supplier->purchases()->exists()) {
            return back()->with('error', 'This supplier is linked to products or purchases and cannot be deleted.');
        }
        

        $name = $supplier->name;
        $supplier->delete();

        return back()->with('success', "Supplier {$name} deleted.");
    }
    

    private function validateSupplier(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
