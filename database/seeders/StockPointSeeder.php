<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockPointSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => env('STOCKPOINT_ADMIN_EMAIL', 'admin@stockpoint.test')],
            [
                'name' => 'Store Administrator',
                'role' => User::ROLE_ADMIN,
                'password' => env('STOCKPOINT_ADMIN_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ],
        );

        if (! $admin->isAdmin()) {
            $admin->update(['role' => User::ROLE_ADMIN]);
        }

        User::query()->firstOrCreate(
            ['email' => 'cashier@stockpoint.test'],
            [
                'name' => 'Cashier One',
                'role' => User::ROLE_CASHIER,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $defaults = [
            'business_name' => 'StockPoint Store',
            'vat_number' => '',
            'phone' => '012 555 0100',
            'email' => 'store@stockpoint.test',
            'address' => '12 Main Road, Johannesburg, 2001',
            'vat_rate' => '15',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        if (Product::query()->exists()) {
            return;
        }

        $categories = collect(['Groceries', 'Drinks', 'Household', 'Personal care', 'Snacks'])
            ->mapWithKeys(fn ($name) => [$name => Category::firstOrCreate(['name' => $name])]);

        $suppliers = collect([
            ['name' => 'Fresh Foods Distributors', 'contact_person' => 'Thabo Mokoena', 'email' => 'orders@freshfoods.example', 'phone' => '012 555 0101'],
            ['name' => 'Aqua Beverage Supply', 'contact_person' => 'Lerato Nkosi', 'email' => 'sales@aqua.example', 'phone' => '012 555 0102'],
            ['name' => 'CleanHome Wholesale', 'contact_person' => 'James Dlamini', 'email' => 'info@cleanhome.example', 'phone' => '012 555 0103'],
        ])->mapWithKeys(fn ($data) => [$data['name'] => Supplier::firstOrCreate(['name' => $data['name']], $data)]);

        $catalogue = [
            ['Full Cream Milk 1L', 'MILK-001', '600100100001', 'Groceries', 13.50, 18.99, 24, 8, '🥛', 'Fresh Foods Distributors'],
            ['Brown Bread', 'BREAD-001', '600100100002', 'Groceries', 11.90, 17.50, 18, 6, '🍞', 'Fresh Foods Distributors'],
            ['White Sugar 2kg', 'SUGAR-002', '600100100003', 'Groceries', 33.00, 42.99, 3, 5, '🍚', 'Fresh Foods Distributors'],
            ['Sunflower Oil 2L', 'OIL-002', '600100100004', 'Groceries', 46.50, 59.99, 12, 5, '🫗', 'Fresh Foods Distributors'],
            ['Bottled Water 1.5L', 'WATER-15', '600100100005', 'Drinks', 8.50, 14.00, 36, 10, '💧', 'Aqua Beverage Supply'],
            ['Orange Juice 1L', 'JUICE-001', '600100100006', 'Drinks', 18.00, 26.50, 8, 8, '🧃', 'Aqua Beverage Supply'],
            ['Cola 2L', 'COLA-002', '600100100007', 'Drinks', 17.50, 24.99, 20, 8, '🥤', 'Aqua Beverage Supply'],
            ['Dishwashing Liquid', 'HOME-001', '600100100008', 'Household', 21.00, 31.99, 2, 6, '🧴', 'CleanHome Wholesale'],
            ['Toilet Paper 9 Pack', 'TP-009', '600100100009', 'Household', 45.00, 64.99, 15, 6, '🧻', 'CleanHome Wholesale'],
            ['Bath Soap', 'SOAP-001', '600100100010', 'Personal care', 8.00, 12.99, 28, 10, '🧼', 'CleanHome Wholesale'],
            ['Toothpaste', 'TOOTH-001', '600100100011', 'Personal care', 16.50, 25.99, 11, 6, '🪥', 'CleanHome Wholesale'],
            ['Potato Chips', 'CHIPS-001', '600100100012', 'Snacks', 11.00, 16.99, 19, 8, '🍟', 'Fresh Foods Distributors'],
        ];

        foreach ($catalogue as [$name, $sku, $barcode, $category, $purchasePrice, $price, $stock, $reorder, $emoji, $supplier]) {
            $product = Product::create([
                'category_id' => $categories[$category]->id,
                'supplier_id' => $suppliers[$supplier]->id,
                'name' => $name,
                'sku' => $sku,
                'barcode' => $barcode,
                'purchase_price' => $purchasePrice,
                'price' => $price,
                'stock' => $stock,
                'reorder_level' => $reorder,
                'emoji' => $emoji,
                'is_active' => true,
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $admin->id,
                'type' => 'opening',
                'quantity' => $stock,
                'balance_after' => $stock,
                'reason' => 'Opening stock',
            ]);
        }
    }
}
