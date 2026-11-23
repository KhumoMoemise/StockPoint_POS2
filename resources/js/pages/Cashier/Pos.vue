<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { dateTime, money } from '@/lib/format';

interface PosProduct {
    id: number;
    name: string;
    category: string;
    price: number;
    stock: number;
    barcode: string | null;
    emoji: string;
}

interface ReceiptData {
    receipt: string;
    date: string;
    cashier: string;
    subtotal: number;
    vat: number;
    total: number;
    payment: { method: string; amount: number; change: number };
    items: Array<{ name: string; quantity: number; unit_price: number; line_total: number }>;
    business: { business_name: string; address: string; phone: string; vat_number: string };
}

const props = defineProps<{
    products: PosProduct[];
    categories: string[];
    stats: {
        today_revenue: number;
        today_count: number;
        items_today: number;
        average_sale: number;
        low_stock: number;
    };
    recentSales: Array<{ id: number; receipt: string; date: string; items: number; payment: string; total: number; status: string }>;
    lastSale: ReceiptData | null;
    vatRate: number;
    cashier: { id: number; name: string; email: string };
}>();

const search = ref('');
const activeCategory = ref('All');
const cart = ref<Array<{ id: number; quantity: number }>>([]);

const today = new Intl.DateTimeFormat('en-ZA', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }).format(
    new Date(),
);

const toast = ref<{ message: string; type: string } | null>(null);
let toastTimer: number | undefined;

function notify(message: string, type = 'error') {
    toast.value = { message, type };
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => (toast.value = null), 2600);
}

const categoryList = computed(() => ['All', ...props.categories]);

const filteredProducts = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.products.filter((product) => {
        const matchesCategory = activeCategory.value === 'All' || product.category === activeCategory.value;
        const matchesTerm =
            !term || product.name.toLowerCase().includes(term) || (product.barcode ?? '').includes(term);

        return matchesCategory && matchesTerm;
    });
});

function productById(id: number): PosProduct | undefined {
    return props.products.find((product) => product.id === id);
}

function addToCart(id: number) {
    const product = productById(id);

    if (!product || product.stock <= 0) {
        notify('This product is out of stock.');
        return;
    }

    const line = cart.value.find((item) => item.id === id);

    if (line && line.quantity >= product.stock) {
        notify('You cannot sell more than the available stock.');
        return;
    }

    if (line) {
        line.quantity += 1;
    } else {
        cart.value.push({ id, quantity: 1 });
    }
}

function changeQty(id: number, delta: number) {
    const line = cart.value.find((item) => item.id === id);
    const product = productById(id);

    if (!line || !product) {
        return;
    }

    if (delta > 0 && line.quantity >= product.stock) {
        notify('Available stock limit reached.');
        return;
    }

    line.quantity += delta;

    if (line.quantity <= 0) {
        cart.value = cart.value.filter((item) => item.id !== id);
    }
}

function removeLine(id: number) {
    cart.value = cart.value.filter((item) => item.id !== id);
}

const cartCount = computed(() => cart.value.reduce((sum, line) => sum + line.quantity, 0));
const subtotal = computed(() =>
    cart.value.reduce((sum, line) => sum + (productById(line.id)?.price ?? 0) * line.quantity, 0),
);
const vat = computed(() => subtotal.value * props.vatRate / (100 + props.vatRate));
const total = computed(() => subtotal.value);

function onSearchEnter() {
    const code = search.value.trim();

    if (!code) {
        return;
    }

    const match = props.products.find((product) => product.barcode === code);

    if (match) {
        addToCart(match.id);
        search.value = '';
    } else {
        notify('No product found for that barcode.');
    }
}

function clearSearch() {
    search.value = '';
    activeCategory.value = 'All';
}

/* ---------------- payment ---------------- */
const paymentOpen = ref(false);
const method = ref('Cash');
const amountReceived = ref(0);

const change = computed(() => {
    const received = method.value === 'Cash' ? Number(amountReceived.value || 0) : total.value;

    return Math.max(0, received - total.value);
});

function openPayment() {
    if (!cart.value.length) {
        return;
    }
    method.value = 'Cash';
    amountReceived.value = Number(total.value.toFixed(2));
    paymentOpen.value = true;
}

watch(method, (value) => {
    if (value !== 'Cash') {
        amountReceived.value = Number(total.value.toFixed(2));
    }
});

const saleForm = useForm({
    items: [] as Array<{ product_id: number; quantity: number }>,
    payment_method: 'Cash',
    amount_received: 0,
});

function completeSale() {
    if (method.value === 'Cash' && Number(amountReceived.value) < total.value) {
        notify('Amount received is less than the total due.');
        return;
    }

    saleForm.items = cart.value.map((line) => ({ product_id: line.id, quantity: line.quantity }));
    saleForm.payment_method = method.value;
    saleForm.amount_received = method.value === 'Cash' ? Number(amountReceived.value) : total.value;

    saleForm.post(route('cashier.sales.store'), {
        onSuccess: () => {
            // Inertia preserves component state on form posts, so reset the UI here.
            paymentOpen.value = false;
            cart.value = [];
            amountReceived.value = 0;
        },
        onError: (errors) => {
            notify(Object.values(errors)[0] ?? 'The sale could not be completed.');
        },
    });
}

/* ---------------- receipt ---------------- */
const receiptOpen = ref(false);
const receipt = ref<ReceiptData | null>(null);

function formatReceipt(data: ReceiptData): string {
    const lines = data.items
        .map((item) => `${item.name}\n${item.quantity} x ${money(item.unit_price)}       ${money(item.line_total)}`)
        .join('\n');

    return `             ${data.business.business_name.toUpperCase()}
          SALES RECEIPT
--------------------------------
Receipt: ${data.receipt}
Date: ${dateTime(data.date)}
Cashier: ${data.cashier}
--------------------------------
${lines}
--------------------------------
Subtotal:             ${money(data.subtotal)}
VAT included (${props.vatRate}%):   ${money(data.vat)}
TOTAL:                ${money(data.total)}
Payment:              ${data.payment.method}
Received:             ${money(data.payment.amount)}
Change:               ${money(data.payment.change)}
--------------------------------
       Thank you for shopping!`;
}

const receiptText = computed(() => (receipt.value ? formatReceipt(receipt.value) : ''));

function printReceipt() {
    window.print();
}

watch(
    () => props.lastSale,
    (sale) => {
        if (!sale) {
            return;
        }

        receipt.value = sale;
        receiptOpen.value = true;
        paymentOpen.value = false;
        cart.value = [];
        amountReceived.value = 0;
    },
    { immediate: true },
);

function goToSales() {
    router.visit(route('cashier.sales.index'));
}
</script>

<template>
    <div class="sp-cashier">
        <Head title="Cashier POS" />

        <aside>
            <Link class="brand" :href="route('cashier.dashboard')"><span class="brand-mark">▦</span> StockPoint</Link>

            <div class="staff">
                <small>Signed in as</small>
                <strong>{{ cashier.name }}</strong>
                <span>Cashier account</span>
            </div>

            <div>
                <div class="nav-label">Workspace</div>
                <nav>
                    <a href="#catalogue" class="active"><span class="ico">▣</span> Point of Sale</a>
                    <a href="#recent"><span class="ico">◷</span> Sales history</a>
                </nav>
            </div>

            <div>
                <div class="nav-label">Account</div>
                <nav>
                    <Link :href="route('cashier.sales.index')"><span class="ico">↗</span> All my sales</Link>
                    <a href="#" @click.prevent="router.post(route('logout'))"><span class="ico">↪</span> Sign out</a>
                </nav>
            </div>

            <div class="side-bottom">POS workspace<br />All amounts in South African rand (ZAR)</div>
        </aside>

        <main>
            <div class="mobile-brand"><span class="brand-mark">▦</span> StockPoint</div>

            <header class="topbar">
                <div>
                    <div class="eyebrow">Workspace / Point of Sale</div>
                    <h1>Cashier dashboard</h1>
                </div>
                <div class="top-actions">
                    <div class="date-pill">{{ today }}</div>
                    <div class="shift-pill">● Shift <b>Active</b></div>
                </div>
            </header>

            <section class="stats">
                <div class="stat">
                    <div class="stat-top">Today's sales <span class="stat-icon">💳</span></div>
                    <strong>{{ money(stats.today_revenue) }}</strong>
                    <small>{{ stats.today_count }} completed transaction(s)</small>
                </div>
                <div class="stat">
                    <div class="stat-top">Items sold today <span class="stat-icon">📦</span></div>
                    <strong>{{ stats.items_today }}</strong>
                    <small>Units processed</small>
                </div>
                <div class="stat">
                    <div class="stat-top">Average sale <span class="stat-icon">📈</span></div>
                    <strong>{{ money(stats.average_sale) }}</strong>
                    <small>Per completed transaction</small>
                </div>
                <div class="stat">
                    <div class="stat-top">Low-stock products <span class="stat-icon">⚠️</span></div>
                    <strong>{{ stats.low_stock }}</strong>
                    <small>Notify your administrator</small>
                </div>
            </section>

            <section class="workspace">
                <div class="panel catalog-panel" id="catalogue">
                    <div class="panel-head">
                        <div>
                            <h2>Product catalogue</h2>
                            <p>Select a product to add it to the current sale</p>
                        </div>
                        <button class="secondary" @click="clearSearch">Clear search</button>
                    </div>
                    <div class="catalog-body">
                        <div class="search-row">
                            <div class="search-wrap">
                                <span>⌕</span>
                                <input
                                    v-model="search"
                                    type="search"
                                    placeholder="Search product name or scan barcode..."
                                    autocomplete="off"
                                    @keydown.enter.prevent="onSearchEnter"
                                />
                            </div>
                            <div class="scan-hint">▥ USB scanner ready</div>
                        </div>
                        <div class="categories">
                            <button
                                v-for="category in categoryList"
                                :key="category"
                                class="category"
                                :class="{ active: activeCategory === category }"
                                @click="activeCategory = category"
                            >
                                {{ category }}
                            </button>
                        </div>
                        <div class="products">
                            <button
                                v-for="product in filteredProducts"
                                :key="product.id"
                                class="product"
                                :disabled="product.stock <= 0"
                                @click="addToCart(product.id)"
                            >
                                <div class="product-emoji">{{ product.emoji }}</div>
                                <div class="product-name">{{ product.name }}</div>
                                <div class="product-meta">
                                    <span>{{ product.category }}</span>
                                    <span :class="{ 'stock-low': product.stock <= 5 }">{{ product.stock }} in stock</span>
                                </div>
                                <div class="product-price">{{ money(product.price) }}</div>
                            </button>
                            <div v-if="!filteredProducts.length" class="empty">No matching products found.</div>
                        </div>
                    </div>
                </div>

                <div class="panel cart-panel">
                    <div class="panel-head">
                        <div>
                            <h2>Current sale</h2>
                            <p>{{ cartCount ? cartCount + ' unit(s) in this sale' : 'No items added yet' }}</p>
                        </div>
                        <span class="cart-count">{{ cartCount }} ITEM{{ cartCount === 1 ? '' : 'S' }}</span>
                    </div>
                    <div class="cart-items">
                        <template v-if="cart.length">
                            <div v-for="line in cart" :key="line.id" class="cart-line">
                                <div>
                                    <strong>{{ productById(line.id)?.name }}</strong>
                                    <small>{{ money(productById(line.id)?.price ?? 0) }} each</small>
                                    <button class="remove" @click="removeLine(line.id)">Remove</button>
                                </div>
                                <div class="line-right">
                                    <div class="line-price">{{ money((productById(line.id)?.price ?? 0) * line.quantity) }}</div>
                                    <div class="qty">
                                        <button @click="changeQty(line.id, -1)">−</button>
                                        <span>{{ line.quantity }}</span>
                                        <button @click="changeQty(line.id, 1)">+</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-else class="cart-empty">🛒<br /><br />Your cart is empty.<br />Select products to begin.</div>
                    </div>
                    <div class="summary">
                        <div class="summary-row"><span>Subtotal</span><strong>{{ money(subtotal) }}</strong></div>
                        <div class="summary-row"><span>VAT ({{ vatRate }}%, included in prices)</span><strong>{{ money(vat) }}</strong></div>
                        <div class="summary-row total"><span>Total due</span><strong>{{ money(total) }}</strong></div>
                    </div>
                    <div class="checkout">
                        <button class="primary" :disabled="!cart.length" @click="openPayment">Proceed to payment →</button>
                    </div>
                </div>
            </section>

            <section class="panel recent" id="recent">
                <div class="panel-head">
                    <div>
                        <h2>Recent transactions</h2>
                        <p>Your latest completed sales</p>
                    </div>
                    <button class="secondary" @click="goToSales">View all sales</button>
                </div>
                <div style="overflow-x: auto">
                    <table class="recent-table">
                        <thead>
                            <tr><th>Receipt no.</th><th>Time</th><th>Items</th><th>Payment</th><th>Total</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="sale in recentSales" :key="sale.id">
                                <td><strong>{{ sale.receipt }}</strong></td>
                                <td>{{ dateTime(sale.date) }}</td>
                                <td>{{ sale.items }}</td>
                                <td>{{ sale.payment }}</td>
                                <td><strong>{{ money(sale.total) }}</strong></td>
                                <td><span class="status">Completed</span></td>
                            </tr>
                            <tr v-if="!recentSales.length">
                                <td colspan="6" style="text-align: center; color: #8995a8">No transactions yet</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <div v-if="paymentOpen" class="modal-backdrop" @click.self="paymentOpen = false">
            <div class="modal">
                <h3>Complete payment</h3>
                <p>Confirm the payment method and amount received.</p>
                <div class="eyebrow">Amount due</div>
                <div class="payment-total">{{ money(total) }}</div>
                <div class="field">
                    <label for="paymentMethod">Payment method</label>
                    <select id="paymentMethod" v-model="method">
                        <option>Cash</option>
                        <option>Card</option>
                        <option>Instant EFT</option>
                    </select>
                </div>
                <div class="field">
                    <label for="amountReceived">Amount received (R)</label>
                    <input
                        id="amountReceived"
                        v-model.number="amountReceived"
                        type="number"
                        min="0"
                        step="0.01"
                        :disabled="method !== 'Cash'"
                        placeholder="Enter amount received"
                    />
                </div>
                <div class="summary-row"><span>Change</span><strong>{{ money(change) }}</strong></div>
                <div class="modal-actions">
                    <button class="secondary" @click="paymentOpen = false">Cancel</button>
                    <button class="primary" :disabled="saleForm.processing" @click="completeSale">Complete sale</button>
                </div>
            </div>
        </div>

        <div v-if="receiptOpen && receipt" class="modal-backdrop" @click.self="receiptOpen = false">
            <div class="modal">
                <h3>Sale completed</h3>
                <p>Payment recorded and stock updated.</p>
                <div class="receipt">{{ receiptText }}</div>
                <div class="modal-actions">
                    <button class="secondary" @click="receiptOpen = false">Close</button>
                    <button class="primary" @click="printReceipt">Print receipt</button>
                </div>
            </div>
        </div>

        <pre class="sp-print-root">{{ receiptText }}</pre>

        <div v-if="toast" class="toast show" :class="toast.type">{{ toast.message }}</div>
    </div>
</template>
