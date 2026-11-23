<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'payment_method' => ['required', Rule::in(['Cash', 'Card', 'Instant EFT'])],
            'amount_received' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $user = $request->user();

        $sale = DB::transaction(function () use ($request, $validated, $user) {
            $ids = collect($validated['items'])->pluck('product_id')->sort()->values();

            $products = Product::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $lines = [];

            foreach ($validated['items'] as $index => $item) {
                $product = $products->get($item['product_id']);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => 'This product is no longer available for sale.',
                    ]);
                }

                $quantity = (int) $item['quantity'];

                if ($quantity > $product->stock) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Insufficient stock for {$product->name}. Only {$product->stock} available.",
                    ]);
                }

                $lineTotal = round((float) $product->price * $quantity, 2);
                $subtotal += $lineTotal;

                $lines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => round((float) $product->price, 2),
                    'line_total' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);
            $vatRate = (float) Setting::get('vat_rate', 15);
            $vat = round($subtotal * $vatRate / (100 + $vatRate), 2);

            $received = $validated['payment_method'] === 'Cash'
                ? round((float) ($validated['amount_received'] ?? 0), 2)
                : $subtotal;

            if ($validated['payment_method'] === 'Cash' && $received < $subtotal) {
                throw ValidationException::withMessages([
                    'amount_received' => 'Amount received is less than the total due.',
                ]);
            }

            $change = $received > $subtotal ? round($received - $subtotal, 2) : 0.0;

            $sale = Sale::create([
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'vat' => $vat,
                'total' => $subtotal,
                'status' => 'completed',
            ]);

            $sale->update([
                'receipt_number' => 'SP-'.$sale->created_at->format('Ymd').'-'.str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT),
            ]);

            foreach ($lines as $line) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'sku' => $line['product']->sku,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                ]);

                $newStock = $line['product']->stock - $line['quantity'];
                $line['product']->update(['stock' => $newStock]);

                StockMovement::create([
                    'product_id' => $line['product']->id,
                    'user_id' => $user->id,
                    'type' => 'sale',
                    'quantity' => -$line['quantity'],
                    'balance_after' => $newStock,
                    'reference' => $sale->receipt_number,
                    'reason' => 'Sale to customer',
                ]);
            }

            Payment::create([
                'sale_id' => $sale->id,
                'method' => $validated['payment_method'],
                'amount' => $received,
                'change' => $change,
            ]);

            return $sale;
        });

        return redirect()
            ->route('cashier.dashboard')
            ->with('success', "Sale {$sale->receipt_number} completed.")
            ->with('sale_id', $sale->id);
    }
}
