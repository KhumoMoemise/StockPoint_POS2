<script setup lang="ts">
import { reactive, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dateTime, money } from '@/lib/format';

const props = defineProps<{
    sales: Array<{
        id: number;
        receipt: string;
        date: string;
        cashier: string;
        items: number;
        payment: string;
        subtotal: number;
        vat: number;
        total: number;
        status: string;
    }>;
    filters: { q: string; payment: string; from: string; to: string };
    stats: { revenue: number; transactions: number; units: number; average: number };
    paymentMethods: string[];
}>();

const local = reactive({ ...props.filters });
let debounce: number | undefined;

function apply() {
    router.get(route('admin.sales.index'), { ...local }, { preserveState: true, preserveScroll: true, replace: true });
}

watch(
    () => local.q,
    () => {
        window.clearTimeout(debounce);
        debounce = window.setTimeout(apply, 350);
    },
);

watch(() => [local.payment, local.from, local.to], apply);

const exportUrl = () => route('admin.sales.export', { ...local });
</script>

<template>
    <AdminLayout title="Sales & invoices" crumb="Sales & invoices" active="sales">
        <Head title="Sales & invoices" />

        <div class="page-intro">
            <div>
                <h2>Sales &amp; invoices</h2>
                <p>Review cashier transactions and print customer receipts.</p>
            </div>
            <a class="btn" :href="exportUrl()">↓ Export sales</a>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-top">Revenue <span class="stat-icon">💳</span></div>
                <strong>{{ money(stats.revenue) }}</strong>
                <small>Matching current filters</small>
            </div>
            <div class="stat">
                <div class="stat-top">Transactions <span class="stat-icon">🧾</span></div>
                <strong>{{ stats.transactions }}</strong>
                <small>Completed sales</small>
            </div>
            <div class="stat">
                <div class="stat-top">Units sold <span class="stat-icon">📦</span></div>
                <strong>{{ stats.units }}</strong>
                <small>Across transactions</small>
            </div>
            <div class="stat">
                <div class="stat-top">Average sale <span class="stat-icon">📈</span></div>
                <strong>{{ money(stats.average) }}</strong>
                <small>Average transaction value</small>
            </div>
        </div>

        <div class="panel">
            <div class="toolbar">
                <input v-model="local.q" class="search" placeholder="Search receipt or cashier..." />
                <select v-model="local.payment" class="btn">
                    <option value="">All payment methods</option>
                    <option v-for="method in paymentMethods" :key="method" :value="method">{{ method }}</option>
                </select>
                <input v-model="local.from" class="btn" type="date" title="From date" />
                <input v-model="local.to" class="btn" type="date" title="To date" />
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Receipt</th>
                            <th>Date / time</th>
                            <th>Cashier</th>
                            <th>Items</th>
                            <th>Payment</th>
                            <th>VAT (15%)</th>
                            <th>Total</th>
                            <th>Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sale in sales" :key="sale.id">
                            <td><strong>{{ sale.receipt }}</strong></td>
                            <td>{{ dateTime(sale.date) }}</td>
                            <td>{{ sale.cashier }}</td>
                            <td>{{ sale.items }}</td>
                            <td>{{ sale.payment }}</td>
                            <td>{{ money(sale.vat) }}</td>
                            <td><strong>{{ money(sale.total) }}</strong></td>
                            <td>
                                <a class="btn btn-small" :href="route('sales.receipt', sale.id)" target="_blank">View / print</a>
                            </td>
                        </tr>
                        <tr v-if="!sales.length">
                            <td colspan="8" class="empty">No sales match your filters yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
