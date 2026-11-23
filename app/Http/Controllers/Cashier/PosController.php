<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $products = Product::query()
            ->active()
            ->where('stock', '>', 0)
            ->with('category:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category?->name ?? 'Uncategorised',
                'price' => (float) $product->price,
                'stock' => $product->stock,
                'barcode' => $product->barcode,
                'emoji' => $product->emoji,
            ]);

        $todaySales = Sale::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereDate('created_at', today());

        $todayRevenue = (float) (clone $todaySales)->sum('total');
        $todayCount = (clone $todaySales)->count();
        $itemsToday = (int) SaleItem::query()
            ->whereIn('sale_id', (clone $todaySales)->select('id'))
            ->sum('quantity');

        $recentSales = Sale::query()
            ->with(['payment:id,sale_id,method', 'items'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'receipt' => $sale->receipt_number,
                'date' => $sale->created_at->toIso8601String(),
                'items' => $sale->items->sum('quantity'),
                'payment' => $sale->payment?->method ?? '—',
                'total' => (float) $sale->total,
                'status' => $sale->status,
            ]);

        $lastSale = null;
        if ($saleId = $request->session()->get('sale_id')) {
            $sale = Sale::query()->where('user_id', $user->id)->find($saleId);
            $lastSale = $sale?->toReceiptData();
        }

        return Inertia::render('Cashier/Pos', [
            'products' => $products,
            'categories' => Category::query()
                ->whereHas('products', fn ($query) => $query->active()->where('stock', '>', 0))
                ->orderBy('name')
                ->pluck('name'),
            'stats' => [
                'today_revenue' => $todayRevenue,
                'today_count' => $todayCount,
                'items_today' => $itemsToday,
                'average_sale' => $todayCount > 0 ? round($todayRevenue / $todayCount, 2) : 0.0,
                'low_stock' => Product::lowStock()->count(),
            ],
            'recentSales' => $recentSales,
            'lastSale' => $lastSale,
            'vatRate' => (float) Setting::get('vat_rate', 15),
            'cashier' => $user->only(['id', 'name', 'email']),
        ]);
    }

    public function sales(Request $request): Response
    {
        $user = $request->user();

        $sales = Sale::query()
            ->with(['payment:id,sale_id,method', 'items'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'receipt' => $sale->receipt_number,
                'date' => $sale->created_at->toIso8601String(),
                'items' => $sale->items->sum('quantity'),
                'payment' => $sale->payment?->method ?? '—',
                'subtotal' => (float) $sale->subtotal,
                'vat' => (float) $sale->vat,
                'total' => (float) $sale->total,
                'status' => $sale->status,
            ]);

        return Inertia::render('Cashier/Sales', [
            'sales' => $sales,
            'cashier' => $user->only(['id', 'name', 'email']),
            'stats' => [
                'revenue' => (float) Sale::where('user_id', $user->id)->where('status', 'completed')->sum('total'),
                'transactions' => Sale::where('user_id', $user->id)->where('status', 'completed')->count(),
            ],
        ]);
    }
}
