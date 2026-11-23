<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::query()->with(['category:id,name', 'supplier:id,name']);

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category_id', $category);
        }

        match ($request->input('stock')) {
            'low' => $query->lowStock(),
            'out' => $query->outOfStock(),
            'healthy' => $query->where('stock', '>', 0)->whereColumn('stock', '>', 'reorder_level'),
            default => null,
        };

        $products = $query->withCount(['saleItems', 'purchaseItems'])
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'category_id' => $product->category_id,
                'category' => $product->category?->name,
                'supplier_id' => $product->supplier_id,
                'supplier' => $product->supplier?->name,
                'purchase_price' => (float) $product->purchase_price,
                'price' => (float) $product->price,
                'stock' => $product->stock,
                'reorder_level' => $product->reorder_level,
                'emoji' => $product->emoji,
                'is_active' => $product->is_active,
                'status' => $product->stockStatus(),
                'has_history' => ($product->sale_items_count + $product->purchase_items_count) > 0,
            ]);

        return Inertia::render('Admin/Products', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'q' => $request->input('q', ''),
                'category' => $request->input('category', ''),
                'stock' => $request->input('stock', 'all'),
            ],
            'stats' => $this->stats(),
            'openCreate' => $request->boolean('create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $categoryId = $this->resolveCategory($request);

        $product = DB::transaction(function () use ($validated, $categoryId, $request) {
            $sku = filled($validated['sku'] ?? null)
                ? Str::upper(trim($validated['sku']))
                : $this->generateSku($validated['name']);

            $product = Product::create([
                'category_id' => $categoryId,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'name' => trim($validated['name']),
                'sku' => $sku,
                'barcode' => filled($validated['barcode'] ?? null) ? trim($validated['barcode']) : null,
                'purchase_price' => round((float) ($validated['purchase_price'] ?? 0), 2),
                'price' => round((float) $validated['price'], 2),
                'stock' => (int) ($validated['stock'] ?? 0),
                'reorder_level' => (int) ($validated['reorder_level'] ?? 0),
                'emoji' => $validated['emoji'] ?: '🛒',
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            if ($product->stock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'opening',
                    'quantity' => $product->stock,
                    'balance_after' => $product->stock,
                    'reason' => 'Opening stock',
                ]);
            }

            return $product;
        });

        return back()->with('success', "Product {$product->name} registered.");
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request, $product);
        $categoryId = $this->resolveCategory($request);

        DB::transaction(function () use ($request, $product, $validated, $categoryId) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            $newStock = (int) ($validated['stock'] ?? $product->stock);
            if ($newStock < 0) {
                throw ValidationException::withMessages(['stock' => 'Stock cannot be negative.']);
            }

            $sku = filled($validated['sku'] ?? null)
                ? Str::upper(trim($validated['sku']))
                : $product->sku;

            $previousStock = $product->stock;

            $product->update([
                'category_id' => $categoryId,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'name' => trim($validated['name']),
                'sku' => $sku,
                'barcode' => filled($validated['barcode'] ?? null) ? trim($validated['barcode']) : null,
                'purchase_price' => round((float) ($validated['purchase_price'] ?? 0), 2),
                'price' => round((float) $validated['price'], 2),
                'stock' => $newStock,
                'reorder_level' => (int) ($validated['reorder_level'] ?? 0),
                'emoji' => $validated['emoji'] ?: '🛒',
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            if ($newStock !== $previousStock) {
                $difference = $newStock - $previousStock;
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'adjustment',
                    'quantity' => $difference,
                    'balance_after' => $newStock,
                    'reason' => 'Stock edited on product form',
                ]);
            }
        });

        return back()->with('success', "Product {$product->name} updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->saleItems()->exists() || $product->purchaseItems()->exists()) {
            return back()->with('error', 'This product has sales or purchase history and cannot be deleted. Mark it inactive instead.');
        }

        $name = $product->name;
        $product->delete();

        return back()->with('success', "Product {$name} deleted.");
    }

    public function adjustStock(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['add', 'remove', 'set'])],
            'quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $product, $validated) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $quantity = (int) $validated['quantity'];

            $newStock = match ($validated['action']) {
                'add' => $product->stock + $quantity,
                'remove' => $product->stock - $quantity,
                'set' => $quantity,
            };

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock cannot become negative.',
                ]);
            }

            $difference = $newStock - $product->stock;
            $product->update(['stock' => $newStock]);

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'type' => 'adjustment',
                'quantity' => $difference,
                'balance_after' => $newStock,
                'reason' => $validated['reason'] ?: 'Manual stock adjustment',
            ]);
        });

        return back()->with('success', 'Stock quantity updated.');
    }

    public function movements(Product $product): JsonResponse
    {
        $movements = $product->stockMovements()
            ->with('user:id,name')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'balance_after' => $movement->balance_after,
                'reference' => $movement->reference,
                'reason' => $movement->reason,
                'user' => $movement->user?->name ?? 'System',
                'date' => $movement->created_at->toIso8601String(),
            ]);

        return response()->json([
            'product' => ['id' => $product->id, 'name' => $product->name, 'stock' => $product->stock],
            'movements' => $movements,
        ]);
    }

    public function export(): StreamedResponse
    {
        $products = Product::query()->with(['category:id,name', 'supplier:id,name'])->orderBy('name')->get();

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'SKU', 'Barcode', 'Category', 'Supplier', 'Purchase price', 'Selling price', 'Quantity', 'Reorder level']);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->name,
                    $product->sku,
                    $product->barcode,
                    $product->category?->name,
                    $product->supplier?->name,
                    number_format((float) $product->purchase_price, 2, '.', ''),
                    number_format((float) $product->price, 2, '.', ''),
                    $product->stock,
                    $product->reorder_level,
                ]);
            }

            fclose($handle);
        }, 'stockpoint-products-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable', 'string', 'max:64',
                Rule::unique('products', 'sku')->ignore($product?->id),
            ],
            'barcode' => [
                'nullable', 'string', 'max:64',
                Rule::unique('products', 'barcode')->ignore($product?->id),
            ],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'new_category' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'emoji' => ['nullable', 'string', 'max:16'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function resolveCategory(Request $request): ?int
    {
        if (filled($request->input('new_category'))) {
            return Category::firstOrCreate(['name' => trim($request->input('new_category'))])->id;
        }

        return $request->filled('category_id') ? (int) $request->input('category_id') : null;
    }

    private function generateSku(string $name): string
    {
        $base = Str::upper(Str::slug($name, '-')) ?: 'SKU';
        $base = Str::limit($base, 20, '');

        do {
            $sku = $base.'-'.Str::upper(Str::random(4));
        } while (Product::query()->where('sku', $sku)->exists());

        return $sku;
    }

    private function stats(): array
    {
        return [
            'stock_units' => (int) Product::sum('stock'),
            'stock_value' => (float) Product::query()->selectRaw('COALESCE(SUM(stock * price), 0) as value')->value('value'),
            'low_stock' => Product::lowStock()->count(),
            'out_of_stock' => Product::outOfStock()->count(),
        ];
    }
}
