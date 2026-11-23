<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function index(): Response
    {
        $purchases = Purchase::query()
            ->with(['supplier:id,name', 'creator:id,name', 'receiver:id,name', 'items.product:id,name,sku'])
            ->latest()
            ->get()
            ->map(fn (Purchase $purchase) => [
                'id' => $purchase->id,
                'reference' => $purchase->reference,
                'supplier' => $purchase->supplier?->name,
                'status' => $purchase->status,
                'total' => (float) $purchase->total,
                'notes' => $purchase->notes,
                'created_by' => $purchase->creator?->name,
                'received_by' => $purchase->receiver?->name,
                'created_at' => $purchase->created_at->toIso8601String(),
                'received_at' => $purchase->received_at?->toIso8601String(),
                'items' => $purchase->items->map(fn (PurchaseItem $item) => [
                    'id' => $item->id,
                    'product' => $item->product?->name ?? 'Deleted product',
                    'sku' => $item->product?->sku,
                    'quantity' => $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'subtotal' => (float) $item->subtotal,
                ]),
            ]);

        return Inertia::render('Admin/Purchases', [
            'purchases' => $purchases,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'purchase_price', 'stock'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'purchase_price' => (float) $product->purchase_price,
                    'stock' => $product->stock,
                ]),
            'stats' => [
                'total_purchases' => Purchase::count(),
                'pending' => Purchase::where('status', 'pending')->count(),
                'received' => Purchase::where('status', 'received')->count(),
                'total_value' => (float) Purchase::sum('total'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ]);

        $purchase = DB::transaction(function () use ($request, $validated) {
            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'created_by' => $request->user()->id,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'total' => 0,
            ]);

            $purchase->update([
                'reference' => 'PO-'.$purchase->created_at->format('Ymd').'-'.str_pad((string) $purchase->id, 4, '0', STR_PAD_LEFT),
            ]);

            $total = 0;

            foreach ($validated['items'] as $item) {
                $subtotal = round((float) $item['unit_cost'] * (int) $item['quantity'], 2);
                $total += $subtotal;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_cost' => round((float) $item['unit_cost'], 2),
                    'subtotal' => $subtotal,
                ]);
            }

            $purchase->update(['total' => round($total, 2)]);

            return $purchase;
        });

        return back()->with('success', "Purchase order {$purchase->reference} created. Receive it to add stock.");
    }

    public function receive(Request $request, Purchase $purchase): RedirectResponse
    {
        $received = DB::transaction(function () use ($request, $purchase) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->isReceived()) {
                return false;
            }

            $purchase->load('items');

            foreach ($purchase->items as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);

                if (! $product) {
                    continue;
                }

                $newStock = $product->stock + $item->quantity;
                $product->update(['stock' => $newStock]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'purchase',
                    'quantity' => $item->quantity,
                    'balance_after' => $newStock,
                    'reference' => $purchase->reference,
                    'reason' => 'Purchase received from '.($purchase->supplier?->name ?? 'supplier'),
                ]);
            }

            $purchase->update([
                'status' => 'received',
                'received_by' => $request->user()->id,
                'received_at' => now(),
            ]);

            return true;
        });

        if (! $received) {
            return back()->with('error', 'This purchase has already been received. Stock was not increased twice.');
        }

        return back()->with('success', "Purchase {$purchase->reference} received and stock updated.");
    }
}
