<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import ModalShell from '@/components/StockPoint/ModalShell.vue';
import { dateTime, money } from '@/lib/format';

const props = defineProps<{
    purchases: Array<{
        id: number;
        reference: string | null;
        supplier: string | null;
        status: string;
        total: number;
        notes: string | null;
        created_by: string | null;
        received_by: string | null;
        created_at: string;
        received_at: string | null;
        items: Array<{ id: number; product: string; sku: string | null; quantity: number; unit_cost: number; subtotal: number }>;
    }>;
    suppliers: Array<{ id: number; name: string }>;
    products: Array<{ id: number; name: string; sku: string; purchase_price: number; stock: number }>;
    stats: { total_purchases: number; pending: number; received: number; total_value: number };
}>();

const modal = ref(false);
const viewModal = ref(false);
const viewing = ref<(typeof props.purchases)[number] | null>(null);

const form = useForm<{
    supplier_id: string | number;
    notes: string;
    items: Array<{ product_id: string | number; quantity: number; unit_cost: number }>;
}>({
    supplier_id: '',
    notes: '',
    items: [],
});

function openCreate() {
    form.reset();
    form.clearErrors();
    form.supplier_id = '';
    form.notes = '';
    form.items = [{ product_id: '', quantity: 1, unit_cost: 0 }];
    modal.value = true;
}

function addLine() {
    form.items.push({ product_id: '', quantity: 1, unit_cost: 0 });
}

function removeLine(index: number) {
    form.items.splice(index, 1);
}

function onProductChange(index: number) {
    const line = form.items[index];
    const product = props.products.find((p) => String(p.id) === String(line.product_id));
    if (product) {
        line.unit_cost = product.purchase_price;
    }
}

const draftTotal = computed(() =>
    form.items.reduce((sum, line) => sum + Number(line.unit_cost || 0) * Number(line.quantity || 0), 0),
);

function submit() {
    form.post(route('admin.purchases.store'), {
        onSuccess: () => (modal.value = false),
    });
}

function receive(purchase: (typeof props.purchases)[number]) {
    if (window.confirm(`Receive ${purchase.reference}? Stock will be increased once.`)) {
        router.post(route('admin.purchases.receive', purchase.id));
    }
}

function view(purchase: (typeof props.purchases)[number]) {
    viewing.value = purchase;
    viewModal.value = true;
}
</script>

<template>
    <AdminLayout title="Purchases" crumb="Purchases" active="purchases">
        <Head title="Purchases" />

        <div class="page-intro">
            <div>
                <h2>Stock purchases</h2>
                <p>Create purchase orders and receive stock from suppliers.</p>
            </div>
            <button class="btn btn-primary" @click="openCreate">＋ New purchase</button>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-top">Purchase orders <span class="stat-icon">🧾</span></div>
                <strong>{{ stats.total_purchases }}</strong>
                <small>All time</small>
            </div>
            <div class="stat">
                <div class="stat-top">Pending receipt <span class="stat-icon">⏳</span></div>
                <strong>{{ stats.pending }}</strong>
                <small>Awaiting stock intake</small>
            </div>
            <div class="stat">
                <div class="stat-top">Received <span class="stat-icon">✅</span></div>
                <strong>{{ stats.received }}</strong>
                <small>Stock already added</small>
            </div>
            <div class="stat">
                <div class="stat-top">Total value <span class="stat-icon">💵</span></div>
                <strong>{{ money(stats.total_value) }}</strong>
                <small>At purchase cost</small>
            </div>
        </div>

        <div class="panel">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Supplier</th>
                            <th>Created</th>
                            <th>Created by</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Received by</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="purchase in purchases" :key="purchase.id">
                            <td><strong>{{ purchase.reference }}</strong></td>
                            <td>{{ purchase.supplier }}</td>
                            <td>{{ dateTime(purchase.created_at) }}</td>
                            <td>{{ purchase.created_by || '—' }}</td>
                            <td>{{ purchase.items.length }}</td>
                            <td><strong>{{ money(purchase.total) }}</strong></td>
                            <td>
                                <span class="badge" :class="purchase.status === 'received' ? '' : 'warn'">
                                    {{ purchase.status === 'received' ? 'Received' : 'Pending' }}
                                </span>
                            </td>
                            <td>{{ purchase.received_by || '—' }}</td>
                            <td>
                                <div class="action-row">
                                    <button class="btn btn-small" @click="view(purchase)">View</button>
                                    <button
                                        v-if="purchase.status !== 'received'"
                                        class="btn btn-small btn-primary"
                                        @click="receive(purchase)"
                                    >
                                        Receive
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!purchases.length">
                            <td colspan="9" class="empty">No purchase orders yet. Create one to receive stock.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ModalShell v-if="modal" title="New purchase order" subtitle="Order stock from a supplier, then receive it to add inventory." wide @close="modal = false">
            <form @submit.prevent="submit">
                <div class="form-grid">
                    <div class="field">
                        <label>Supplier *</label>
                        <select v-model="form.supplier_id" required>
                            <option value="">Select a supplier</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                        </select>
                        <div v-if="form.errors.supplier_id" class="field-error">{{ form.errors.supplier_id }}</div>
                    </div>
                    <div class="field">
                        <label>Notes</label>
                        <input v-model="form.notes" placeholder="Optional reference or delivery note" />
                    </div>
                </div>

                <div class="nav-label" style="padding: 0; margin: 16px 0 8px">Order lines</div>
                <div class="line-items">
                    <div v-for="(line, index) in form.items" :key="index" class="line-item">
                        <div class="field">
                            <label>Product</label>
                            <select v-model="line.product_id" required @change="onProductChange(index)">
                                <option value="">Select product</option>
                                <option v-for="product in products" :key="product.id" :value="product.id">
                                    {{ product.name }} ({{ product.sku }})
                                </option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Qty</label>
                            <input v-model.number="line.quantity" type="number" min="1" step="1" required />
                        </div>
                        <div class="field">
                            <label>Unit cost (R)</label>
                            <input v-model.number="line.unit_cost" type="number" min="0" step="0.01" required />
                        </div>
                        <button type="button" class="btn btn-danger" @click="removeLine(index)">Remove</button>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px">
                    <button type="button" class="btn" @click="addLine">＋ Add line</button>
                    <strong>Order total: {{ money(draftTotal) }}</strong>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn" @click="modal = false">Cancel</button>
                    <button class="btn btn-primary" :disabled="form.processing">Create purchase</button>
                </div>
            </form>
        </ModalShell>

        <ModalShell v-if="viewModal && viewing" :title="`Purchase ${viewing.reference}`" :subtitle="`${viewing.supplier} · ${viewing.status === 'received' ? 'Received' : 'Pending'}`" wide @close="viewModal = false">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Unit cost</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in viewing.items" :key="item.id">
                            <td><strong>{{ item.product }}</strong></td>
                            <td>{{ item.sku || '—' }}</td>
                            <td>{{ item.quantity }}</td>
                            <td>{{ money(item.unit_cost) }}</td>
                            <td><strong>{{ money(item.subtotal) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="notice">
                Created {{ dateTime(viewing.created_at) }} by {{ viewing.created_by || '—' }}.
                <template v-if="viewing.received_at"> Received {{ dateTime(viewing.received_at) }} by {{ viewing.received_by }}.</template>
            </div>
            <div class="modal-actions">
                <button class="btn" @click="viewModal = false">Close</button>
            </div>
        </ModalShell>
    </AdminLayout>
</template>
