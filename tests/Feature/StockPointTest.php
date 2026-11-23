<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockPointTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function cashier(): User
    {
        return User::factory()->create(['role' => User::ROLE_CASHIER]);
    }

    private function product(array $attributes = []): Product
    {
        static $counter = 0;
        $counter++;

        return Product::create(array_merge([
            'name' => 'Test Product '.$counter,
            'sku' => 'SKU-'.$counter,
            'price' => 100,
            'purchase_price' => 60,
            'stock' => 10,
            'reorder_level' => 3,
            'emoji' => '🛒',
            'is_active' => true,
        ], $attributes));
    }

    public function test_cashiers_cannot_access_the_admin_area()
    {
        $this->actingAs($this->cashier())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admins_cannot_access_the_cashier_pos()
    {
        $this->actingAs($this->admin())
            ->get(route('cashier.dashboard'))
            ->assertForbidden();
    }

    public function test_products_are_created_with_an_opening_stock_movement()
    {
        $response = $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name' => 'White Sugar 2kg',
            'price' => 45.99,
            'stock' => 24,
            'reorder_level' => 5,
            'emoji' => '🍬',
        ]);

        $response->assertSessionHasNoErrors();

        $product = Product::firstWhere('name', 'White Sugar 2kg');

        $this->assertNotNull($product);
        $this->assertSame(24, $product->stock);
        $this->assertNotEmpty($product->sku);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'opening',
            'quantity' => 24,
            'balance_after' => 24,
        ]);
    }

    public function test_stock_can_never_be_adjusted_below_zero()
    {
        $product = $this->product(['stock' => 3]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.stock', $product), [
                'action' => 'remove',
                'quantity' => 5,
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_receiving_a_purchase_increases_stock_only_once()
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Acme Wholesale']);
        $product = $this->product(['stock' => 5]);

        $this->actingAs($admin)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 4, 'unit_cost' => 10],
            ],
        ])->assertSessionHasNoErrors();

        $purchase = Purchase::firstOrFail();

        $this->actingAs($admin)->post(route('admin.purchases.receive', $purchase));
        $this->assertSame(9, $product->fresh()->stock);

        $this->actingAs($admin)->post(route('admin.purchases.receive', $purchase));
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertSame('received', $purchase->fresh()->status);
    }

    public function test_checkout_deducts_stock_and_records_the_sale()
    {
        $cashier = $this->cashier();
        $product = $this->product(['price' => 115, 'stock' => 10]);

        $response = $this->actingAs($cashier)->post(route('cashier.sales.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'Cash',
            'amount_received' => 250,
        ]);

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHasNoErrors();

        $sale = Sale::firstOrFail();

        $this->assertSame('230.00', (string) $sale->total);
        $this->assertSame('30.00', (string) $sale->vat);
        $this->assertMatchesRegularExpression('/^SP-\d{8}-\d{5}$/', $sale->receipt_number);
        $this->assertSame(8, $product->fresh()->stock);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'method' => 'Cash',
            'amount' => 250,
            'change' => 20,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'sale',
            'quantity' => -2,
            'balance_after' => 8,
        ]);
    }

    public function test_checkout_cannot_sell_more_than_available_stock()
    {
        $product = $this->product(['stock' => 2]);

        $this->actingAs($this->cashier())
            ->post(route('cashier.sales.store'), [
                'items' => [['product_id' => $product->id, 'quantity' => 5]],
                'payment_method' => 'Card',
                'amount_received' => 500,
            ])
            ->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(0, Sale::count());
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_cash_payment_below_the_total_is_rejected()
    {
        $product = $this->product(['price' => 100, 'stock' => 5]);

        $this->actingAs($this->cashier())
            ->post(route('cashier.sales.store'), [
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'Cash',
                'amount_received' => 50,
            ])
            ->assertSessionHasErrors('amount_received');

        $this->assertSame(0, Sale::count());
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_admin_csv_exports_stream_csv_content()
    {
        $admin = $this->admin();
        $this->product();

        foreach (['admin.products.export', 'admin.sales.export', 'admin.reports.export'] as $route) {
            $response = $this->actingAs($admin)->get(route($route));

            $response->assertOk();
            $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        }
    }

    public function test_reports_page_renders_for_admins()
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports.index'))
            ->assertOk();
    }

    public function test_cashiers_can_only_view_their_own_receipts()
    {
        $owner = $this->cashier();
        $other = $this->cashier();
        $product = $this->product(['price' => 50, 'stock' => 5]);

        $this->actingAs($owner)->post(route('cashier.sales.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'Instant EFT',
            'amount_received' => 50,
        ]);

        $sale = Sale::firstOrFail();

        $this->actingAs($owner)->get(route('sales.receipt', $sale))->assertOk();
        $this->actingAs($other)->get(route('sales.receipt', $sale))->assertForbidden();
        $this->actingAs($this->admin())->get(route('sales.receipt', $sale))->assertOk();
    }
}
