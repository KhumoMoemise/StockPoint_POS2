<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import BarChart from '@/components/StockPoint/BarChart.vue';
import { money } from '@/lib/format';

const props = defineProps<{
    filters: { from: string; to: string };
    summary: { revenue: number; transactions: number; units: number; average: number };
    daily: Array<{ label: string; revenue: number; transactions: number }>;
    monthly: Array<{ label: string; revenue: number }>;
    bestSellers: Array<{ name: string; quantity: number; revenue: number }>;
    byCashier: Array<{ name: string; revenue: number; transactions: number }>;
    byPayment: Array<{ method: string; revenue: number; transactions: number }>;
    stock: { units: number; retail_value: number; cost_value: number };
    lowStock: Array<{ id: number; name: string; sku: string; category: string | null; stock: number; reorder_level: number; status: string }>;
    outOfStock: Array<{ id: number; name: string; sku: string; category: string | null; stock: number; reorder_level: number; status: string }>;
}>();

const local = reactive({ ...props.filters });

function apply() {
    router.get(route('admin.reports.index'), { ...local }, { preserveState: true, preserveScroll: true });
}

const dailyData = computed(() => props.daily.map((d) => ({ label: d.label, value: d.revenue })));
const monthlyData = computed(() => props.monthly.map((d) => ({ label: d.label, value: d.revenue })));
const cashierData = computed(() => props.byCashier.map((d) => ({ label: d.name, value: d.revenue })));
const paymentData = computed(() => props.byPayment.map((d) => ({ label: d.method, value: d.revenue })));

const maxBestSeller = computed(() => Math.max(1, ...props.bestSellers.map((b) => b.quantity)));

function printReport() {
    window.print();
}
</script>

<template>
    <AdminLayout title="Reports & analytics" crumb="Reports & analytics" active="reports">
        <Head title="Reports & analytics" />

        <div class="page-intro">
            <div>
                <h2>Reports &amp; analytics</h2>
                <p>Review sales performance and export transaction data.</p>
            </div>
            <div class="action-row">
                <button class="btn" @click="printReport">🖨 Print report</button>
                <a class="btn btn-primary" :href="route('admin.reports.export', { ...local })">↓ Download CSV report</a>
            </div>
        </div>

        <div class="panel" style="margin-bottom: 15px">
            <div class="toolbar">
                <label style="font-size: 11px; color: #718096">From</label>
                <input v-model="local.from" class="btn" type="date" />
                <label style="font-size: 11px; color: #718096">To</label>
                <input v-model="local.to" class="btn" type="date" />
                <button class="btn btn-primary" @click="apply">Apply filters</button>
            </div>
        </div>

        <div class="report-cards">
            <div class="report-card"><span>Revenue recorded</span><strong>{{ money(summary.revenue) }}</strong></div>
            <div class="report-card"><span>Transactions</span><strong>{{ summary.transactions }}</strong></div>
            <div class="report-card"><span>Units sold</span><strong>{{ summary.units }}</strong></div>
            <div class="report-card"><span>Average sale</span><strong>{{ money(summary.average) }}</strong></div>
        </div>

        <div class="grid-two">
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Sales by day</h3>
                        <p>Revenue grouped by date in the selected range</p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="chart-wrap"><BarChart :data="dailyData" /></div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Monthly revenue</h3>
                        <p>Last 12 months</p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="chart-wrap"><BarChart :data="monthlyData" color="#16845b" /></div>
                </div>
            </div>
        </div>

        <div class="grid-two">
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Sales by cashier</h3>
                        <p>Revenue per staff account</p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="chart-wrap"><BarChart :data="cashierData" color="#7c3aed" /></div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Sales by payment method</h3>
                        <p>Cash, card and Instant EFT</p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="chart-wrap"><BarChart :data="paymentData" color="#b7791f" /></div>
                </div>
            </div>
        </div>

        <div class="grid-two">
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Best-selling products</h3>
                        <p>By units sold in the selected range</p>
                    </div>
                </div>
                <div class="panel-body">
                    <div v-if="bestSellers.length" style="display: grid; gap: 14px">
                        <div v-for="product in bestSellers" :key="product.name">
                            <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 5px">
                                <strong>{{ product.name }}</strong>
                                <span class="muted">{{ product.quantity }} sold · {{ money(product.revenue) }}</span>
                            </div>
                            <div class="sp-bar-track">
                                <div class="sp-bar-fill" :style="{ width: (product.quantity / maxBestSeller) * 100 + '%', background: '#2563eb' }"></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="empty">No sales recorded in this period.</div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Stock valuation</h3>
                        <p>Current inventory value</p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="stats" style="grid-template-columns: repeat(2, 1fr); margin: 0">
                        <div class="stat" style="box-shadow: none">
                            <div class="stat-top">Units on hand</div>
                            <strong>{{ stock.units }}</strong>
                        </div>
                        <div class="stat" style="box-shadow: none">
                            <div class="stat-top">Retail value</div>
                            <strong>{{ money(stock.retail_value) }}</strong>
                        </div>
                        <div class="stat" style="box-shadow: none">
                            <div class="stat-top">Cost value</div>
                            <strong>{{ money(stock.cost_value) }}</strong>
                        </div>
                        <div class="stat" style="box-shadow: none">
                            <div class="stat-top">Potential margin</div>
                            <strong>{{ money(stock.retail_value - stock.cost_value) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-two">
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Low-stock products</h3>
                        <p>At or below reorder level</p>
                    </div>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Product</th><th>Category</th><th>Qty</th><th>Reorder</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in lowStock" :key="product.id">
                                <td><strong>{{ product.name }}</strong></td>
                                <td>{{ product.category || '—' }}</td>
                                <td><strong>{{ product.stock }}</strong></td>
                                <td>{{ product.reorder_level }}</td>
                            </tr>
                            <tr v-if="!lowStock.length"><td colspan="4" class="empty">No low-stock products.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Out-of-stock products</h3>
                        <p>Unavailable to sell</p>
                    </div>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Product</th><th>Category</th><th>SKU</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in outOfStock" :key="product.id">
                                <td><strong>{{ product.name }}</strong></td>
                                <td>{{ product.category || '—' }}</td>
                                <td>{{ product.sku }}</td>
                            </tr>
                            <tr v-if="!outOfStock.length"><td colspan="3" class="empty">No out-of-stock products.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
