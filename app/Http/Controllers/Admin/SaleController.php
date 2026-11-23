<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Sale::query()->with(['user:id,name', 'payment:id,sale_id,method', 'items'])->where('status', 'completed');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            });
        }

        if ($method = $request->input('payment')) {
            $query->whereHas('payment', fn ($payment) => $payment->where('method', $method));
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $filtered = clone $query;

        $sales = $query->latest()->limit(200)->get()->map(fn (Sale $sale) => $this->saleRow($sale));

        return Inertia::render('Admin/Sales', [
            'sales' => $sales,
            'filters' => [
                'q' => $request->input('q', ''),
                'payment' => $request->input('payment', ''),
                'from' => $request->input('from', ''),
                'to' => $request->input('to', ''),
            ],
            'stats' => [
                'revenue' => (float) (clone $filtered)->sum('total'),
                'transactions' => (clone $filtered)->count(),
                'units' => SaleItem::query()
                    ->whereIn('sale_id', (clone $filtered)->select('id'))
                    ->sum('quantity'),
                'average' => (float) (clone $filtered)->avg('total'),
            ],
            'paymentMethods' => Payment::query()->distinct()->orderBy('method')->pluck('method'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Sale::query()->with(['user:id,name', 'payment:id,sale_id,method', 'items'])->where('status', 'completed');

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($method = $request->input('payment')) {
            $query->whereHas('payment', fn ($payment) => $payment->where('method', $method));
        }

        $sales = $query->latest()->get();

        return response()->streamDownload(function () use ($sales) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Receipt', 'Date', 'Cashier', 'Items', 'Payment', 'Subtotal', 'VAT (15%)', 'Total']);

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->receipt_number,
                    $sale->created_at->format('Y-m-d H:i:s'),
                    $sale->user?->name,
                    $sale->items->sum('quantity'),
                    $sale->payment?->method,
                    number_format((float) $sale->subtotal, 2, '.', ''),
                    number_format((float) $sale->vat, 2, '.', ''),
                    number_format((float) $sale->total, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'stockpoint-sales-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function saleRow(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'receipt' => $sale->receipt_number,
            'date' => $sale->created_at->toIso8601String(),
            'cashier' => $sale->user?->name ?? 'Unknown',
            'items' => $sale->items->sum('quantity'),
            'payment' => $sale->payment?->method ?? '—',
            'subtotal' => (float) $sale->subtotal,
            'vat' => (float) $sale->vat,
            'total' => (float) $sale->total,
            'status' => $sale->status,
        ];
    }
}
