<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    title: string;
    crumb: string;
    active: string;
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);

const nav = [
    { key: 'overview', label: 'Overview', icon: '▦', route: 'admin.dashboard' },
    { key: 'products', label: 'Products & stock', icon: '▤', route: 'admin.products.index' },
    { key: 'suppliers', label: 'Suppliers', icon: '♧', route: 'admin.suppliers.index' },
    { key: 'purchases', label: 'Purchases', icon: '⇪', route: 'admin.purchases.index' },
    { key: 'sales', label: 'Sales & invoices', icon: '↗', route: 'admin.sales.index' },
    { key: 'reports', label: 'Reports & analytics', icon: '▥', route: 'admin.reports.index' },
];

const today = new Intl.DateTimeFormat('en-ZA', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }).format(
    new Date(),
);

const toast = ref<{ message: string; type: string } | null>(null);
let timer: number | undefined;

function notify(message: string, type = 'success') {
    toast.value = { message, type };
    window.clearTimeout(timer);
    timer = window.setTimeout(() => (toast.value = null), 2800);
}

watch(
    () => (page.props as any).flash,
    (flash: any) => {
        if (flash?.success) {
            notify(flash.success, 'success');
        }
        if (flash?.error) {
            notify(flash.error, 'error');
        }
    },
    { deep: true, immediate: true },
);

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="sp-admin">
        <aside>
            <Link class="brand" :href="route('admin.dashboard')">
                <span class="brand-mark">▦</span> StockPoint
            </Link>

            <div class="admin-card">
                <small>Signed in as</small>
                <strong>{{ user?.name }}</strong>
                <span>Administrator account</span>
            </div>

            <div>
                <div class="nav-label">Management</div>
                <nav>
                    <Link
                        v-for="item in nav"
                        :key="item.key"
                        :href="route(item.route)"
                        :class="{ active: active === item.key }"
                    >
                        <span class="ico">{{ item.icon }}</span> {{ item.label }}
                    </Link>
                </nav>
            </div>

            <div>
                <div class="nav-label">System</div>
                <nav>
                    <Link :href="route('admin.settings.index')" :class="{ active: active === 'settings' }">
                        <span class="ico">⚙</span> Settings
                    </Link>
                    <a href="#" @click.prevent="logout"><span class="ico">↪</span> Sign out</a>
                </nav>
            </div>

            <div class="side-foot">Admin workspace<br />Currency: South African rand (ZAR)</div>
        </aside>

        <main>
            <div class="mobile-brand"><span class="brand-mark">▦</span> StockPoint</div>

            <nav class="mobile-nav">
                <Link
                    v-for="item in nav"
                    :key="item.key"
                    :href="route(item.route)"
                    :class="{ active: active === item.key }"
                >
                    {{ item.label }}
                </Link>
                <Link :href="route('admin.settings.index')" :class="{ active: active === 'settings' }">Settings</Link>
            </nav>

            <header class="topbar">
                <div>
                    <div class="eyebrow">Management / <span>{{ crumb }}</span></div>
                    <h1>{{ title }}</h1>
                </div>
                <div class="top-right">
                    <div class="date-pill">{{ today }}</div>
                    <div class="role-pill">Role: <b>Admin</b></div>
                </div>
            </header>

            <slot />

            <div v-if="toast" class="toast show" :class="toast.type">{{ toast.message }}</div>
        </main>
    </div>
</template>
