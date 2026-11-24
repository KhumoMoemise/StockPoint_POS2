<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\PurchaseController as AdminPurchaseController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SaleController as AdminSaleController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Cashier\PosController;
use App\Http\Controllers\Cashier\SaleController as CashierSaleController;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if ($user = Auth::user()) {
        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'cashier.dashboard');
    }

    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    $user = Auth::user();

    return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'cashier.dashboard');
})->middleware('auth')->name('dashboard');

$infoPages = [
    'about' => [
        'title' => 'About StockPoint',
        'body' => 'StockPoint is a point of sale and inventory management system built for South African retailers. It combines a fast cashier till with live stock control, supplier purchasing, invoicing and reporting.',
    ],
    'contact' => [
        'title' => 'Contact us',
        'body' => 'Our local support team is available 24/7. Email support@stockpoint.test or call +27 12 555 0100 and we will get back to you within one business day.',
    ],
    'careers' => [
        'title' => 'Careers',
        'body' => 'We are a small team building practical retail software from South Africa. Send your CV and a short note about the work you enjoy to careers@stockpoint.test.',
    ],
    'privacy' => [
        'title' => 'Privacy policy',
        'body' => '# STOCKPOINT – PRIVACY POLICY

 1. Introduction

Welcome to StockPoint.

StockPoint is an inventory management and Point of Sale (POS) system designed to help businesses manage products, stock levels, suppliers, purchases, sales, receipts, employees and business reports.

We respect your privacy and are committed to protecting personal information collected through the StockPoint application.

This Privacy Policy explains what information we collect, why we collect it, how we use and protect it, and what rights you have regarding your personal information.

This policy is intended to align with the Protection of Personal Information Act 4 of 2013 (POPIA) of South Africa.

## 2. Who We Are

StockPoint is operated by:

- **Business/organisation name:** [Insert registered business or owner name]
- **Application name:** StockPoint
- **Business address:** [Insert business address]
- **Contact email:** [Insert official email address]
- **Telephone:** [Insert contact number]

For this policy, "we", "our" and "us" refer to the organisation responsible for operating StockPoint.

## 3. Information We Collect

Depending on how StockPoint is configured and used, we may collect the following information:

### 3.1 User and Account Information

When an administrator or cashier registers or uses StockPoint, the system may collect:

- Full name and surname.
- Email address.
- Login credentials, stored in protected form.
- User role (Administrator or Cashier).
- Account creation and activity information.

### 3.2 Business Information

To operate the inventory and POS functions, the system may store:

- Business and store details.
- Product names, descriptions and categories.
- Product prices, SKUs and barcodes.
- Stock quantities and stock movement records.
- Supplier names and contact details.
- Purchase and supplier transaction records.

### 3.3 Sales and Customer Information

When a business processes sales, StockPoint may store:

- Transaction and invoice numbers.
- Products purchased.
- Quantities and prices.
- Payment method and transaction totals.
- VAT and applicable tax calculations.
- Cashier responsible for the transaction.
- Customer details, where entered by the business.

StockPoint does not require a business to collect customer personal information unless the relevant feature or business process requires it.

### 3.4 Technical Information

Depending on the applications configuration, we may collect technical information such as browser type, device information, IP address, access logs and error reports to help maintain and troubleshoot the system.

## 4. How We Use Your Information

Information collected through StockPoint may be used to:

- Create and manage administrator and cashier accounts.
- Authenticate users and control access to the system.
- Manage products, inventory and stock movements.
- Record purchases and supplier transactions.
- Process sales and generate receipts or invoices.
- Calculate transaction totals and applicable VAT.
- Generate sales, inventory and business reports.
- Monitor system performance and troubleshoot errors.
- Prevent unauthorised access and misuse.
- Meet applicable legal, accounting and record-keeping obligations.

We will not use personal information for unrelated purposes without an appropriate lawful basis.

## 5. User Roles and Access

StockPoint supports different user roles to protect business information.

**Administrators**

Administrators may have access to business management functions, including products, inventory, suppliers, purchases, sales, reports and cashier account management.

**Cashiers**

Cashiers are provided with access to the POS functions required to process sales. Their access to administrative functions and other users information is restricted according to their assigned permissions.

Users must keep their login credentials confidential and must not share their accounts with unauthorised individuals.

## 6. Data Storage and Security

We take reasonable technical and organisational measures to protect information stored in StockPoint against:

- Unauthorised access.
- Accidental loss or destruction.
- Unlawful disclosure.
- Unauthorised modification.
- Misuse of personal information.

Security measures may include authentication, role-based access controls, password protection, database access restrictions, backups and system monitoring, depending on the deployed configuration.

No electronic system can guarantee complete security. Users and businesses should also protect their devices, networks and login credentials.

## 7. Sharing of Information

We do not sell personal information to third parties.

Information may be shared only where reasonably necessary, including:

- With authorised employees who need access to perform their duties.
- With hosting, infrastructure or technical service providers who support the operation of StockPoint.
- With payment service providers, where a payment integration is used.
- With professional advisers where necessary.
- With law enforcement, regulators or other authorities where required or permitted by law.
- Where the relevant individual has provided valid consent or another lawful basis applies.

Where third-party service providers process personal information on our behalf, appropriate confidentiality and security obligations should be established.

## 8. Data Retention

We retain personal information only for as long as necessary to fulfil the purposes for which it was collected, unless a longer retention period is required or authorised by law.

Business transaction records, invoices, accounting information and related records may need to be retained to comply with applicable legal and financial requirements.

When information is no longer required, reasonable steps will be taken to securely delete, destroy or de-identify it, where appropriate.

## 9. Your Privacy Rights

Under POPIA, individuals may have the right to:

- Request confirmation of whether we hold their personal information.
- Request access to their personal information.
- Request correction of inaccurate or incomplete information.
- Request deletion where legally permitted.
- Object to certain forms of processing.
- Withdraw consent where processing is based on consent.
- Lodge a complaint with the Information Regulator.

These rights are subject to applicable legal requirements and limitations.

To exercise these rights, contact us using the details in Section 14.

## 10. Cookies and Website Technologies

If StockPoint uses cookies or similar technologies, they may be used to:

- Maintain login sessions.
- Support essential application functionality.
- Remember certain user preferences.
- Improve system performance and security.

Essential cookies may be necessary for the application to function correctly. Any optional analytics or tracking technologies should be disclosed and managed in accordance with applicable privacy requirements.

## 11. Third-Party Services and External Links

StockPoint may use or connect to third-party services, such as hosting providers, email services, payment gateways or reporting tools.

These providers may have their own privacy policies and terms of service. We encourage users to review those policies before using integrated services.

We are not responsible for the privacy practices of external websites or services that are not operated by us.

## 12. Childrens Privacy

StockPoint is a business management application and is not intended to be used by children for independent account registration.

We do not knowingly seek to collect childrens personal information unless it is lawfully required for a specific business purpose.

## 13. Changes to This Privacy Policy

We may update this Privacy Policy when StockPoints features, operations or legal requirements change.

The latest version will be made available through the application or our official website. Significant changes will be communicated where required by law.

Continued use of StockPoint after an updated policy becomes effective will be subject to the updated policy, to the extent permitted by applicable law.

## 14. Contact Us

If you have questions about this Privacy Policy, wish to request access to your information, or want to raise a privacy concern, contact:

**StockPoint Privacy Contact**

- Organisation: [Insert business/owner name]
- Email: [Insert privacy contact email]
- Telephone: [Insert telephone number]
- Address: [Insert physical or registered address]


---

**StockPoint – Simplifying Inventory and Point of Sale Management.**',
    ],
    'terms' => [
        'title' => 'Terms of service',
        'body' => 'StockPoint is provided as a business tool. You are responsible for the accuracy of the data you capture and for keeping staff credentials secure. The 14-day trial has no setup fee and can be cancelled at any time.',
    ],
    'popia' => [
        'title' => 'POPIA compliance',
        'body' => 'StockPoint is designed to support South African POPIA obligations: role-based access limits what staff can see, personal data is kept to the minimum needed to operate the store, and account data can be removed on request.',
    ],
];

foreach ($infoPages as $slug => $page) {
    Route::get($slug, fn () => Inertia::render('Info', [
        'title' => $page['title'],
        'body' => $page['body'],
    ]))->name('info.'.$slug);
}

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    //PRODUCTS
    Route::resource('products', AdminProductController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('products/{product}/stock', [AdminProductController::class, 'adjustStock'])->name('products.stock');
    Route::get('products/{product}/movements', [AdminProductController::class, 'movements'])->name('products.movements');
    Route::get('products-export', [AdminProductController::class, 'export'])->name('products.export');

    //SUPPLIERS
    Route::resource('suppliers', AdminSupplierController::class)->only(['index', 'store', 'update', 'destroy']);

    //PURCHASES
    Route::get('purchases', [AdminPurchaseController::class, 'index'])->name('purchases.index');
    Route::post('purchases', [AdminPurchaseController::class, 'store'])->name('purchases.store');
    Route::post('purchases/{purchase}/receive', [AdminPurchaseController::class, 'receive'])->name('purchases.receive');

    //SALES
    Route::get('sales', [AdminSaleController::class, 'index'])->name('sales.index');
    Route::get('sales-export', [AdminSaleController::class, 'export'])->name('sales.export');

    //REPORTS
    Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('reports-export', [AdminReportController::class, 'export'])->name('reports.export');

    //ADMIN COTROLL
    Route::get('settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('cashiers', [AdminSettingsController::class, 'storeCashier'])->name('cashiers.store');
    Route::delete('cashiers/{user}', [AdminSettingsController::class, 'destroyCashier'])->name('cashiers.destroy');
});

Route::middleware(['auth', 'role:cashier'])->prefix('cashier')->name('cashier.')->group(function () {
    Route::get('/', [PosController::class, 'index'])->name('dashboard');
    Route::get('sales', [PosController::class, 'sales'])->name('sales.index');
    Route::post('sales', [CashierSaleController::class, 'store'])->name('sales.store');
});

Route::middleware('auth')->get('sales/{sale}/receipt', [ReceiptController::class, 'show'])->name('sales.receipt');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
