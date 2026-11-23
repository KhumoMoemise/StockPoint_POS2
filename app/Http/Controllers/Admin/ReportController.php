<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $salesQuery = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to]);

        $revenue = (float) (clone $salesQuery)->sum('total');
        $transactions = (clone $salesQuery)->count();
        $units = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to])
            ->sum('sale_items.quantity');

        $dailyRows = (clone $salesQuery)
            ->selectRaw('date(created_at) as day, SUM(total) as revenue, COUNT(*) as transactions')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $daily = [];
        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $row = $dailyRows->get($date->toDateString());
            $daily[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('d M'),
                'revenue' => (float) ($row->revenue ?? 0),
                'transactions' => (int) ($row->transactions ?? 0),
            ];
        }

        $monthStart = Carbon::now()->startOfMonth()->subMonths(11);
        $monthlySales = Sale::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $monthStart)
            ->get(['total', 'created_at']);

        $monthly = [];
        for ($date = $monthStart->copy(); $date->lte(Carbon::now()->startOfMonth()); $date->addMonth()) {
            $key = $date->format('Y-m');
            $monthly[] = [
                'month' => $key,
                'label' => $date->format('M Y'),
                'revenue' => (float) $monthlySales->filter(fn (Sale $sale) => $sale->created_at->format('Y-m') === $key)->sum('total'),
            ];
        }

        $bestSellers = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to])
            ->selectRaw('sale_items.product_name as name, SUM(sale_items.quantity) as quantity, SUM(sale_items.line_total) as revenue')
            ->groupBy('sale_items.product_name')
            ->orderByDesc('quantity')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
            ]);

        $byCashier = Sale::query()
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to])
            ->selectRaw('users.name as name, SUM(sales.total) as revenue, COUNT(sales.id) as transactions')
            ->groupBy('users.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'revenue' => (float) $row->revenue,
                'transactions' => (int) $row->transactions,
            ]);

        $byPayment = Payment::query()
            ->join('sales', 'payments.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to])
            ->selectRaw('payments.method as method, SUM(sales.total) as revenue, COUNT(DISTINCT sales.id) as transactions')
            ->groupBy('payments.method')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method,
                'revenue' => (float) $row->revenue,
                'transactions' => (int) $row->transactions,
            ]);

        $lowStock = Product::query()
            ->with('category:id,name')
            ->lowStock()
            ->orderBy('stock')
            ->limit(50)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category?->name,
                'stock' => $product->stock,
                'reorder_level' => $product->reorder_level,
                'status' => $product->stockStatus(),
            ]);

        $outOfStock = Product::query()
            ->with('category:id,name')
            ->outOfStock()
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category?->name,
                'stock' => $product->stock,
                'reorder_level' => $product->reorder_level,
                'status' => $product->stockStatus(),
            ]);

        return Inertia::render('Admin/Reports', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'revenue' => $revenue,
                'transactions' => $transactions,
                'units' => (int) $units,
                'average' => $transactions > 0 ? round($revenue / $transactions, 2) : 0.0,
            ],
            'daily' => $daily,
            'monthly' => $monthly,
            'bestSellers' => $bestSellers,
            'byCashier' => $byCashier,
            'byPayment' => $byPayment,
            'stock' => [
                'units' => (int) Product::sum('stock'),
                'retail_value' => (float) Product::query()->selectRaw('COALESCE(SUM(stock * price), 0) as value')->value('value'),
                'cost_value' => (float) Product::query()->selectRaw('COALESCE(SUM(stock * purchase_price), 0) as value')->value('value'),
            ],
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);

        $sales = Sale::query()
            ->with(['user:id,name', 'payment:id,sale_id,method', 'items'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($sales) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Receipt', 'Date', 'Cashier', 'Payment', 'Items', 'Subtotal', 'VAT', 'Total']);

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->receipt_number,
                    $sale->created_at->format('Y-m-d H:i:s'),
                    $sale->user?->name,
                    $sale->payment?->method,
                    $sale->items->sum('quantity'),
                    number_format((float) $sale->subtotal, 2, '.', ''),
                    number_format((float) $sale->vat, 2, '.', ''),
                    number_format((float) $sale->total, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'stockpoint-report-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366)->startOfDay();
        }

        return [$from, $to];
    }
}
