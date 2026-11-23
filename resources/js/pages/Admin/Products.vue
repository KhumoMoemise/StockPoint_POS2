<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import ModalShell from '@/components/StockPoint/ModalShell.vue';
import { dateTime, money } from '@/lib/format';

const props = defineProps<{
    products: Array<{
        id: number;
        name: string;
        sku: string;
        barcode: string | null;
        category_id: number | null;
        category: string | null;
        supplier_id: number | null;
        supplier: string | null;
        purchase_price: number;
        price: number;
        stock: number;
        reorder_level: number;
        emoji: string;
        is_active: boolean;
        status: string;
        has_history: boolean;
    }>;
    categories: Array<{ id: number; name: string }>;
    suppliers: Array<{ id: number; name: string }>;
    filters: { q: string; category: string; stock: string };
    stats: { stock_units: number; stock_value: number; low_stock: number; out_of_stock: number };
    openCreate: boolean;
}>();

const search = ref('');
const categoryFilter = ref('');
const stockFilter = ref('all');

const filtered = computed(() =>
    props.products.filter((product) => {
        const term = search.value.trim().toLowerCase();
        const matchesTerm =
            !term ||
            product.name.toLowerCase().includes(term) ||
            product.sku.toLowerCase().includes(term) ||
            (product.barcode ?? '').includes(term);
        const matchesCategory = !categoryFilter.value || String(product.category_id) === categoryFilter.value;
        const matchesStock =
            stockFilter.value === 'all' ||
            (stockFilter.value === 'low' && product.status === 'low') ||
            (stockFilter.value === 'out' && product.status === 'out') ||
            (stockFilter.value === 'healthy' && product.status === 'healthy');

        return matchesTerm && matchesCategory && matchesStock;
    }),
);

const productModal = ref(false);
const editingId = ref<number | null>(null);

const productForm = useForm({
    name: '',
    sku: '',
    barcode: '',
    category_id: '' as string | number,
    new_category: '',
    supplier_id: '' as string | number,
    purchase_price: 0,
    price: 0,
    stock: 0,
    reorder_level: 5,
    emoji: '🛒',
    is_active: true,
});

function openCreate() {
    editingId.value = null;
    productForm.reset();
    productForm.clearErrors();
    productForm.reorder_level = 5;
    productForm.emoji = '🛒';
    productForm.is_active = true;
    productModal.value = true;
}

function openEdit(product: (typeof props.products)[number]) {
    editingId.value = product.id;
    productForm.clearErrors();
    productForm.name = product.name;
    productForm.sku = product.sku;
    productForm.barcode = product.barcode ?? '';
    productForm.category_id = product.category_id ?? '';
    productForm.new_category = '';
    productForm.supplier_id = product.supplier_id ?? '';
    productForm.purchase_price = product.purchase_price;
    productForm.price = product.price;
    productForm.stock = product.stock;
    productForm.reorder_level = product.reorder_level;
    productForm.emoji = product.emoji;
    productForm.is_active = product.is_active;
    productModal.value = true;
}

function submitProduct() {
    const options = { onSuccess: () => (productModal.value = false) };

    if (editingId.value) {
        productForm.put(route('admin.products.update', editingId.value), options);
    } else {
        productForm.post(route('admin.products.store'), options);
    }
}

const stockModal = ref(false);
const stockProduct = ref<(typeof props.products)[number] | null>(null);
const stockForm = useForm({ action: 'add', quantity: 0, reason: '' });

function openStock(product: (typeof props.products)[number]) {
    stockProduct.value = product;
    stockForm.reset();
    stockForm.clearErrors();
    stockForm.action = 'add';
    stockModal.value = true;
}

function submitStock() {
    if (!stockProduct.value) {
        return;
    }
    stockForm.post(route('admin.products.stock', stockProduct.value.id), {
        onSuccess: () => (stockModal.value = false),
    });
}

const historyModal = ref(false);
const historyProduct = ref<(typeof props.products)[number] | null>(null);
const movements = ref<Array<any>>([]);
const loadingHistory = ref(false);

async function openHistory(product: (typeof props.products)[number]) {
    historyProduct.value = product;
    movements.value = [];
    historyModal.value = true;
    loadingHistory.value = true;

    try {
        const response = await fetch(route('admin.products.movements', product.id), {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();
        movements.value = data.movements ?? [];
    } finally {
        loadingHistory.value = false;
    }
}

function destroyProduct(product: (typeof props.products)[number]) {
    if (window.confirm(`Delete ${product.name}? This cannot be undone.`)) {
        router.delete(route('admin.products.destroy', product.id));
    }
}

function statusLabel(status: string): string {
    return status === 'out' ? 'Out of stock' : status === 'low' ? 'Low stock' : 'In stock';
}

if (props.openCreate) {
    openCreate();
}
</script>

<template>
    <AdminLayout title="Products & stock" crumb="Products & stock" active="products">
        <Head title="Products & stock" />

        <div class="page-intro">
            <div>
                <h2>Products &amp; inventory</h2>
                <p>Register products, maintain stock levels and manage barcodes.</p>
            </div>
            <button class="btn btn-primary" @click="openCreate">＋ Add product</button>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-top">Total stock units <span class="stat-icon">📦</span></div>
                <strong>{{ stats.stock_units }}</strong>
                <small>Across all products</small>
            </div>
            <div class="stat">
                <div class="stat-top">Stock value <span class="stat-icon">💵</span></div>
                <strong>{{ money(stats.stock_value) }}</strong>
                <small>At current selling prices</small>
            </div>
            <div class="stat">
                <div class="stat-top">Low stock <span class="stat-icon">⚠️</span></div>
                <strong>{{ stats.low_stock }}</strong>
                <small>Reorder required</small>
            </div>
            <div class="stat">
                <div class="stat-top">Out of stock <span class="stat-icon">⛔</span></div>
                <strong>{{ stats.out_of_stock }}</strong>
                <small>Unavailable to sell</small>
            </div>
        </div>

        <div class="panel">
            <div class="toolbar">
                <input v-model="search" class="search" placeholder="Search name, SKU or barcode..." />
                <select v-model="categoryFilter" class="btn">
                    <option value="">All categories</option>
                    <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                        {{ category.name }}
                    </option>
                </select>
                <select v-model="stockFilter" class="btn">
                    <option value="all">All stock levels</option>
                    <option value="low">Low stock</option>
                    <option value="out">Out of stock</option>
                    <option value="healthy">In stock</option>
                </select>
                <a class="btn" :href="route('admin.products.export')">↓ Export CSV</a>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Barcode</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Reorder at</th>
                            <th>Stock status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product in filtered" :key="product.id">
                            <td><strong>{{ product.emoji }} {{ product.name }}</strong></td>
                            <td>{{ product.sku }}</td>
                            <td>{{ product.barcode || '—' }}</td>
                            <td>{{ product.category || '—' }}</td>
                            <td>{{ money(product.price) }}</td>
                            <td><strong>{{ product.stock }}</strong></td>
                            <td>{{ product.reorder_level }}</td>
                            <td><span class="badge" :class="product.status">{{ statusLabel(product.status) }}</span></td>
                            <td>
                                <div class="action-row">
                                    <button class="btn btn-small" @click="openEdit(product)">Edit</button>
                                    <button class="btn btn-small" @click="openStock(product)">Adjust</button>
                                    <button class="btn btn-small" @click="openHistory(product)">History</button>
                                    <button class="btn btn-small btn-danger" @click="destroyProduct(product)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!filtered.length">
                            <td colspan="9" class="empty">No products match your search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="notice">
            Barcode scanners that act like a keyboard can enter a barcode into the product search field. SKUs and barcodes are
            validated for uniqueness on the server.
        </div>

        <ModalShell
            v-if="productModal"
            :title="editingId ? 'Edit product' : 'Register product'"
            subtitle="Add a product to the catalogue and set its stock."
            @close="productModal = false"
        >
            <form @submit.prevent="submitProduct">
                <div class="form-grid">
                    <div class="field">
                        <label>Product name *</label>
                        <input v-model="productForm.name" required />
                        <div v-if="productForm.errors.name" class="field-error">{{ productForm.errors.name }}</div>
                    </div>
                    <div class="field">
                        <label>SKU</label>
                        <input v-model="productForm.sku" placeholder="Leave blank to auto-generate" />
                        <div v-if="productForm.errors.sku" class="field-error">{{ productForm.errors.sku }}</div>
                    </div>
                    <div class="field">
                        <label>Barcode</label>
                        <input v-model="productForm.barcode" placeholder="Scan or enter barcode" />
                        <div v-if="productForm.errors.barcode" class="field-error">{{ productForm.errors.barcode }}</div>
                    </div>
                    <div class="field">
                        <label>Emoji</label>
                        <input v-model="productForm.emoji" maxlength="4" placeholder="🛒" />
                    </div>
                    <div class="field">
                        <label>Category</label>
                        <select v-model="productForm.category_id">
                            <option value="">Select a category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div class="field">
                        <label>New category (optional)</label>
                        <input v-model="productForm.new_category" placeholder="e.g. Bakery" />
                        <div v-if="productForm.errors.new_category" class="field-error">{{ productForm.errors.new_category }}</div>
                    </div>
                    <div class="field">
                        <label>Purchase price (R)</label>
                        <input v-model.number="productForm.purchase_price" type="number" min="0" step="0.01" />
                    </div>
                    <div class="field">
                        <label>Selling price (R) *</label>
                        <input v-model.number="productForm.price" type="number" min="0" step="0.01" required />
                        <div v-if="productForm.errors.price" class="field-error">{{ productForm.errors.price }}</div>
                    </div>
                    <div class="field">
                        <label>Quantity in stock *</label>
                        <input v-model.number="productForm.stock" type="number" min="0" step="1" required />
                        <div v-if="productForm.errors.stock" class="field-error">{{ productForm.errors.stock }}</div>
                    </div>
                    <div class="field">
                        <label>Reorder level *</label>
                        <input v-model.number="productForm.reorder_level" type="number" min="0" step="1" required />
                    </div>
                    <div class="field">
                        <label>Supplier</label>
                        <select v-model="productForm.supplier_id">
                            <option value="">No supplier selected</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                {{ supplier.name }}
                            </option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Available for sale</label>
                        <select v-model="productForm.is_active">
                            <option :value="true">Active</option>
                            <option :value="false">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" @click="productModal = false">Cancel</button>
                    <button class="btn btn-primary" :disabled="productForm.processing">Save product</button>
                </div>
            </form>
        </ModalShell>

        <ModalShell v-if="stockModal && stockProduct" title="Adjust stock" :subtitle="`${stockProduct.name} · Current quantity: ${stockProduct.stock}`" @close="stockModal = false">
            <form @submit.prevent="submitStock">
                <div class="form-grid">
                    <div class="field">
                        <label>Adjustment type</label>
                        <select v-model="stockForm.action">
                            <option value="add">Receive stock (+)</option>
                            <option value="remove">Remove stock (−)</option>
                            <option value="set">Set exact quantity</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Quantity</label>
                        <input v-model.number="stockForm.quantity" type="number" min="0" step="1" required />
                        <div v-if="stockForm.errors.quantity" class="field-error">{{ stockForm.errors.quantity }}</div>
                    </div>
                    <div class="field full">
                        <label>Reason / reference</label>
                        <input v-model="stockForm.reason" placeholder="e.g. Supplier delivery note" />
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" @click="stockModal = false">Cancel</button>
                    <button class="btn btn-primary" :disabled="stockForm.processing">Save adjustment</button>
                </div>
            </form>
        </ModalShell>

        <ModalShell v-if="historyModal && historyProduct" :title="`Stock history · ${historyProduct.name}`" subtitle="Latest 50 stock movements" wide @close="historyModal = false">
            <div v-if="loadingHistory" class="empty">Loading movements…</div>
            <div v-else class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Change</th>
                            <th>Balance</th>
                            <th>Reference</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="movement in movements" :key="movement.id">
                            <td>{{ dateTime(movement.date) }}</td>
                            <td><span class="badge info">{{ movement.type }}</span></td>
                            <td>
                                <strong :style="{ color: movement.quantity < 0 ? '#d64545' : '#16845b' }">
                                    {{ movement.quantity > 0 ? '+' : '' }}{{ movement.quantity }}
                                </strong>
                            </td>
                            <td>{{ movement.balance_after }}</td>
                            <td>{{ movement.reference || movement.reason || '—' }}</td>
                            <td>{{ movement.user }}</td>
                        </tr>
                        <tr v-if="!movements.length">
                            <td colspan="6" class="empty">No stock movements recorded.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </ModalShell>
    </AdminLayout>
</template>
