<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptController extends Controller
{
    public function show(Request $request, Sale $sale): Response
    {
        $user = $request->user();

        if (! $user->isAdmin() && $sale->user_id !== $user->id) {
            abort(403, 'You may only view your own receipts.');
        }

        return Inertia::render('Sales/Receipt', [
            'sale' => $sale->toReceiptData(),
        ]);
    }
}
