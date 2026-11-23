<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="sp-auth">
        <Head title="Log in" />

        <aside class="side">
            <Link class="brand" :href="route('home')"><span class="brand-mark">▦</span> StockPoint</Link>
            <h2>Welcome back to your store.</h2>
            <p>
                Sign in to open the point of sale, manage inventory and keep your sales, stock and suppliers in sync — all from one
                dashboard.
            </p>
            <ul>
                <li><i>✓</i> Fast cashier POS with barcode support</li>
                <li><i>✓</i> Live stock levels and low-stock alerts</li>
                <li><i>✓</i> VAT-inclusive pricing at 15%</li>
                <li><i>✓</i> Printable receipts and CSV reports</li>
            </ul>
            <div class="side-foot">Made in South Africa 🇿🇦 · All amounts in ZAR</div>
        </aside>

        <main class="panel">
            <div class="card">
                <h1>Sign in</h1>
                <p class="sub">Enter your email and password to continue.</p>

                <div v-if="status" class="alert ok">{{ status }}</div>

                <form @submit.prevent="submit">
                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" v-model="form.email" type="email" required autofocus autocomplete="email" placeholder="you@example.co.za" />
                        <div v-if="form.errors.email" class="alert err" style="margin: 8px 0 0">{{ form.errors.email }}</div>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" v-model="form.password" type="password" required autocomplete="current-password" placeholder="••••••••" />
                        <div v-if="form.errors.password" class="alert err" style="margin: 8px 0 0">{{ form.errors.password }}</div>
                    </div>

                    <div class="row">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 600">
                            <input v-model="form.remember" type="checkbox" style="width: auto" /> Remember me
                        </label>
                        <Link v-if="canResetPassword" class="link" :href="route('password.request')">Forgot password?</Link>
                    </div>

                    <button class="btn" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Signing in…' : 'Sign in' }}
                    </button>
                </form>

                <div class="alt">
                    Don't have an account?
                    <Link class="link" :href="route('register')">Create one</Link>
                </div>

                <div class="hint">
                    <b>Demo accounts</b><br />
                    Admin — admin@stockpoint.test / password <br/>
                    Cashier — cashier@stockpoint.test / password <br/>
                    Onthatile — cashier2@stockpoint.test / password <br/>
                    Khumo — moemisekhumo110@gmail.com / password
                </div>
            </div>
        </main>
    </div>
</template>
