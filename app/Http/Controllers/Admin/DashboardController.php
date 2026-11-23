<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = Sale::query()->where('status', 'completed')->whereDate('created_at', today());

        $stats = [
            'today_revenue' => (float) (clone $today)->sum('total'),
            'today_transactions' => (clone $today)->count(),
            'products' => Product::count(),
            'low_stock' => Product::lowStock()->count(),
            'out_of_stock' => Product::outOfStock()->count(),
            'suppliers' => Supplier::count(),
            'stock_units' => (int) Product::sum('stock'),
            'stock_value' => (float) Product::query()->selectRaw('COALESCE(SUM(stock * price), 0) as value')->value('value'),
        ];

        $weekStart = Carbon::today()->subDays(6);
        $daily = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$weekStart->copy()->startOfDay(), Carbon::today()->endOfDay()])
            ->selectRaw('date(created_at) as day, SUM(total) as revenue, COUNT(*) as transactions')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $weekly = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $row = $daily->get($date->toDateString());

            $weekly[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('D'),
                'revenue' => (float) ($row->revenue ?? 0),
                'transactions' => (int) ($row->transactions ?? 0),
            ];
        }

        $alerts = Product::query()
            ->with('category')
            ->where(fn ($query) => $query->lowStock()->orWhere(fn ($q) => $q->outOfStock()))
            ->orderBy('stock')
            ->limit(8)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
                'reorder_level' => $product->reorder_level,
                'category' => $product->category?->name,
                'status' => $product->stockStatus(),
            ]);

        $latestSales = Sale::query()
            ->with(['user:id,name', 'payment:id,sale_id,method'])
            ->where('status', 'completed')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Sale $sale) => $this->saleRow($sale));

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'weekly' => $weekly,
            'alerts' => $alerts,
            'latestSales' => $latestSales,
        ]);
    }

    private function saleRow(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'receipt' => $sale->receipt_number,
            'date' => $sale->created_at->toIso8601String(),
            'cashier' => $sale->user?->name ?? 'Unknown',
            'payment' => $sale->payment?->method ?? '—',
            'total' => (float) $sale->total,
            'status' => $sale->status,
        ];
    }
}
