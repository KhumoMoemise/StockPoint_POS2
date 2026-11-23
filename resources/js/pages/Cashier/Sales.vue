<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { dateTime, money } from '@/lib/format';

defineProps<{
    sales: Array<{
        id: number;
        receipt: string;
        date: string;
        items: number;
        payment: string;
        subtotal: number;
        vat: number;
        total: number;
        status: string;
    }>;
    cashier: { id: number; name: string; email: string };
    stats: { revenue: number; transactions: number };
}>();

const today = new Intl.DateTimeFormat('en-ZA', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }).format(
    new Date(),
);
</script>

<template>
    <div class="sp-cashier">
        <Head title="My sales" />

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
                    <Link :href="route('cashier.dashboard')"><span class="ico">▣</span> Point of Sale</Link>
                    <a href="#" class="active"><span class="ico">◷</span> Sales history</a>
                </nav>
            </div>

            <div>
                <div class="nav-label">Account</div>
                <nav>
                    <a href="#" @click.prevent="router.post(route('logout'))"><span class="ico">↪</span> Sign out</a>
                </nav>
            </div>

            <div class="side-bottom">POS workspace<br />All amounts in South African rand (ZAR)</div>
        </aside>

        <main>
            <div class="mobile-brand"><span class="brand-mark">▦</span> StockPoint</div>

            <header class="topbar">
                <div>
                    <div class="eyebrow">Workspace / Sales history</div>
                    <h1>My sales history</h1>
                </div>
                <div class="top-actions">
                    <div class="date-pill">{{ today }}</div>
                    <Link class="shift-pill" :href="route('cashier.dashboard')">← Back to POS</Link>
                </div>
            </header>

            <section class="stats">
                <div class="stat">
                    <div class="stat-top">Total sales <span class="stat-icon">💳</span></div>
                    <strong>{{ money(stats.revenue) }}</strong>
                    <small>All time</small>
                </div>
                <div class="stat">
                    <div class="stat-top">Transactions <span class="stat-icon">🧾</span></div>
                    <strong>{{ stats.transactions }}</strong>
                    <small>Completed sales</small>
                </div>
                <div class="stat">
                    <div class="stat-top">Average sale <span class="stat-icon">📈</span></div>
                    <strong>{{ money(stats.transactions ? stats.revenue / stats.transactions : 0) }}</strong>
                    <small>Per transaction</small>
                </div>
                <div class="stat">
                    <div class="stat-top">Latest receipt <span class="stat-icon">◷</span></div>
                    <strong>{{ sales.length ? sales[0].receipt : '—' }}</strong>
                    <small>Most recent</small>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <h2>Completed transactions</h2>
                        <p>Your most recent 200 sales</p>
                    </div>
                </div>
                <div style="overflow-x: auto">
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>Receipt no.</th>
                                <th>Date / time</th>
                                <th>Items</th>
                                <th>Payment</th>
                                <th>VAT</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sale in sales" :key="sale.id">
                                <td><strong>{{ sale.receipt }}</strong></td>
                                <td>{{ dateTime(sale.date) }}</td>
                                <td>{{ sale.items }}</td>
                                <td>{{ sale.payment }}</td>
                                <td>{{ money(sale.vat) }}</td>
                                <td><strong>{{ money(sale.total) }}</strong></td>
                                <td><span class="status">Completed</span></td>
                                <td>
                                    <a class="secondary" :href="route('sales.receipt', sale.id)" target="_blank">Print</a>
                                </td>
                            </tr>
                            <tr v-if="!sales.length">
                                <td colspan="8" style="text-align: center; color: #8995a8">No sales recorded yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</template>
