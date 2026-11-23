<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import LineChart from '@/components/StockPoint/LineChart.vue';
import { dateTime, money } from '@/lib/format';

defineProps<{
    stats: {
        today_revenue: number;
        today_transactions: number;
        products: number;
        low_stock: number;
        out_of_stock: number;
        suppliers: number;
        stock_units: number;
        stock_value: number;
    };
    weekly: Array<{ label: string; revenue: number; transactions: number }>;
    alerts: Array<{ id: number; name: string; stock: number; reorder_level: number; category: string | null; status: string }>;
    latestSales: Array<{
        id: number;
        receipt: string;
        date: string;
        cashier: string;
        payment: string;
        total: number;
        status: string;
    }>;
}>();

const chartData = (weekly: Array<{ label: string; revenue: number }>) => weekly.map((d) => ({ label: d.label, value: d.revenue }));
</script>

<template>
    <AdminLayout title="Admin dashboard" crumb="Overview" active="overview">
        <Head title="Admin dashboard" />

        <div class="page-intro">
            <div>
                <h2>Business overview</h2>
                <p>Monitor your store's performance and operations.</p>
            </div>
            <a class="btn" :href="route('admin.sales.export')">↓ Export sales</a>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-top">Today's revenue <span class="stat-icon">💰</span></div>
                <strong>{{ money(stats.today_revenue) }}</strong>
                <small>{{ stats.today_transactions }} transaction(s) today</small>
            </div>
            <div class="stat">
                <div class="stat-top">Products in catalogue <span class="stat-icon">📦</span></div>
                <strong>{{ stats.products }}</strong>
                <small>{{ stats.stock_units }} units in stock</small>
            </div>
            <div class="stat">
                <div class="stat-top">Low-stock alerts <span class="stat-icon">⚠️</span></div>
                <strong>{{ stats.low_stock }}</strong>
                <small>{{ stats.out_of_stock }} out of stock</small>
            </div>
            <div class="stat">
                <div class="stat-top">Suppliers <span class="stat-icon">🚚</span></div>
                <strong>{{ stats.suppliers }}</strong>
                <small>Stock value {{ money(stats.stock_value) }}</small>
            </div>
        </div>

        <div class="grid-two">
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Sales performance</h3>
                        <p>Revenue for the last 7 days</p>
                    </div>
                    <div class="legend"><span>Revenue</span></div>
                </div>
                <div class="panel-body">
                    <div class="chart-wrap">
                        <LineChart :data="chartData(weekly)" />
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Stock alerts</h3>
                        <p>Products that need attention</p>
                    </div>
                    <Link class="btn btn-small" :href="route('admin.products.index', { stock: 'low' })">Manage stock</Link>
                </div>
                <div class="panel-body">
                    <div v-if="alerts.length" class="alert-list">
                        <div v-for="alert in alerts" :key="alert.id" class="alert" :class="{ red: alert.status === 'out' }">
                            <div class="alert-icon">{{ alert.status === 'out' ? '⛔' : '⚠️' }}</div>
                            <div>
                                <strong>{{ alert.name }} — {{ alert.status === 'out' ? 'Out of stock' : 'Low stock' }}</strong>
                                <span>{{ alert.stock }} units remaining · Reorder level {{ alert.reorder_level }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="empty">All products are above their reorder levels.</div>
                </div>
            </div>
        </div>

        <div class="panel" style="margin-bottom: 15px">
            <div class="panel-head">
                <div>
                    <h3>Quick actions</h3>
                    <p>Common administrator tasks</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="quick-actions">
                    <Link class="quick" :href="route('admin.products.index', { create: 1 })">
                        <span>＋</span><strong>Register product</strong><small>Add a new item to the catalogue</small>
                    </Link>
                    <Link class="quick" :href="route('admin.products.index')">
                        <span>▣</span><strong>Update stock</strong><small>Receive or adjust inventory</small>
                    </Link>
                    <Link class="quick" :href="route('admin.suppliers.index', { create: 1 })">
                        <span>♧</span><strong>Add supplier</strong><small>Register a supplier contact</small>
                    </Link>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div>
                    <h3>Latest sales</h3>
                    <p>Most recent completed transactions</p>
                </div>
                <Link class="btn btn-small" :href="route('admin.sales.index')">All sales</Link>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Receipt</th>
                            <th>Date / time</th>
                            <th>Cashier</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sale in latestSales" :key="sale.id">
                            <td><strong>{{ sale.receipt }}</strong></td>
                            <td>{{ dateTime(sale.date) }}</td>
                            <td>{{ sale.cashier }}</td>
                            <td>{{ sale.payment }}</td>
                            <td><strong>{{ money(sale.total) }}</strong></td>
                            <td><span class="badge">Completed</span></td>
                        </tr>
                        <tr v-if="!latestSales.length">
                            <td colspan="6" class="empty">No sales recorded yet. Cashier transactions will appear here.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
