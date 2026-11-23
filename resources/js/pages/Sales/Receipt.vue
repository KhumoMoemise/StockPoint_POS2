<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { dateTime, money } from '@/lib/format';

const props = defineProps<{
    sale: {
        receipt: string;
        date: string;
        cashier: string;
        subtotal: number;
        vat: number;
        total: number;
        status: string;
        payment: { method: string; amount: number; change: number };
        items: Array<{ id: number; name: string; sku: string | null; quantity: number; unit_price: number; line_total: number }>;
        business: { business_name: string; address: string; phone: string; email: string; vat_number: string; vat_rate: number };
    };
}>();

const page = usePage();
const role = computed(() => (page.props.auth as any)?.user?.role);
const backRoute = computed(() => (role.value === 'admin' ? route('admin.sales.index') : route('cashier.sales.index')));

function printReceipt() {
    window.print();
}
</script>

<template>
    <Head :title="`Receipt ${sale.receipt}`" />

    <div class="sp-receipt-page">
        <div class="actions">
            <a :href="backRoute" class="btn">← Back to sales</a>
            <button class="btn btn-primary" @click="printReceipt">🖨 Print receipt</button>
        </div>

        <div class="receipt-card">
            <div class="head">
                <div class="biz">{{ sale.business.business_name }}</div>
                <div class="muted">{{ sale.business.address }}</div>
                <div class="muted">
                    <template v-if="sale.business.phone">{{ sale.business.phone }} · </template>{{ sale.business.email }}
                </div>
                <div class="muted" v-if="sale.business.vat_number">VAT No: {{ sale.business.vat_number }}</div>
                <div class="title">SALES RECEIPT</div>
            </div>

            <div class="meta">
                <div><span>Receipt</span><strong>{{ sale.receipt }}</strong></div>
                <div><span>Date</span><strong>{{ dateTime(sale.date) }}</strong></div>
                <div><span>Cashier</span><strong>{{ sale.cashier }}</strong></div>
                <div><span>Status</span><strong>{{ sale.status }}</strong></div>
            </div>

            <table>
                <thead>
                    <tr><th>Item</th><th>Qty</th><th>Unit</th><th>Total</th></tr>
                </thead>
                <tbody>
                    <tr v-for="item in sale.items" :key="item.id">
                        <td>{{ item.name }}</td>
                        <td>{{ item.quantity }}</td>
                        <td>{{ money(item.unit_price) }}</td>
                        <td>{{ money(item.line_total) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="totals">
                <div><span>Subtotal</span><strong>{{ money(sale.subtotal) }}</strong></div>
                <div><span>VAT included ({{ sale.business.vat_rate }}%)</span><strong>{{ money(sale.vat) }}</strong></div>
                <div class="grand"><span>Total</span><strong>{{ money(sale.total) }}</strong></div>
                <div><span>Payment method</span><strong>{{ sale.payment.method }}</strong></div>
                <div><span>Amount received</span><strong>{{ money(sale.payment.amount) }}</strong></div>
                <div><span>Change</span><strong>{{ money(sale.payment.change) }}</strong></div>
            </div>

            <div class="thanks">Thank you for shopping!</div>
        </div>
    </div>
</template>

<style scoped>
.sp-receipt-page {
    min-height: 100vh;
    background: #f4f7fb;
    padding: 30px 16px 60px;
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
    color: #172033;
}
.actions {
    width: min(560px, 100%);
    margin: 0 auto 16px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
}
.btn {
    border: 1px solid #e7ecf3;
    background: #fff;
    color: #536078;
    padding: 10px 14px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
}
.btn-primary {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}
.receipt-card {
    width: min(560px, 100%);
    margin: 0 auto;
    background: #fff;
    border: 1px solid #e7ecf3;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(22, 34, 55, 0.06);
    padding: 30px;
}
.head {
    text-align: center;
    border-bottom: 1px dashed #cbd5e1;
    padding-bottom: 16px;
}
.biz {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -0.4px;
}
.muted {
    color: #718096;
    font-size: 12px;
}
.title {
    margin-top: 12px;
    font-size: 12px;
    letter-spacing: 2px;
    font-weight: 700;
    color: #2563eb;
}
.meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px 16px;
    margin: 18px 0;
    font-size: 12px;
}
.meta span {
    color: #718096;
    display: block;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
th,
td {
    text-align: left;
    padding: 9px 6px;
    border-bottom: 1px solid #eef2f8;
}
th {
    color: #718096;
    font-weight: 600;
}
td:last-child,
th:last-child,
td:nth-child(2),
th:nth-child(2),
td:nth-child(3),
th:nth-child(3) {
    text-align: right;
}
.totals {
    margin-top: 16px;
    border-top: 1px dashed #cbd5e1;
    padding-top: 14px;
    display: grid;
    gap: 8px;
    font-size: 12px;
}
.totals div {
    display: flex;
    justify-content: space-between;
}
.totals span {
    color: #718096;
}
.totals .grand {
    font-size: 16px;
    font-weight: 800;
    border-top: 1px solid #e7ecf3;
    padding-top: 10px;
    margin-top: 4px;
}
.thanks {
    text-align: center;
    margin-top: 22px;
    color: #718096;
    font-size: 12px;
}
@media print {
    .actions {
        display: none;
    }
    .sp-receipt-page {
        background: #fff;
        padding: 0;
    }
    .receipt-card {
        border: 0;
        box-shadow: none;
        width: 100%;
        padding: 0;
    }
}
</style>
