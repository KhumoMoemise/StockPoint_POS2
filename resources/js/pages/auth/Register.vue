<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="sp-auth">
        <Head title="Register" />

        <aside class="side">
            <Link class="brand" :href="route('home')"><span class="brand-mark">▦</span> StockPoint</Link>
            <h2>Start your 14-day free trial.</h2>
            <p>
                Create your account in seconds and start ringing up sales today. No card required, no setup fees — cancel any time.
            </p>
            <ul>
                <li><i>✓</i> Full point of sale and inventory system</li>
                <li><i>✓</i> Add your products, suppliers and staff</li>
                <li><i>✓</i> Instant reports and CSV exports</li>
                <li><i>✓</i> Local South African support</li>
            </ul>
            <div class="side-foot">Made in South Africa 🇿🇦 · All amounts in ZAR</div>
        </aside>

        <main class="panel">
            <div class="card">
                <h1>Create your account</h1>
                <p class="sub">New accounts are created as cashier users. An administrator can upgrade your access.</p>

                <form @submit.prevent="submit">
                    <div class="field">
                        <label for="name">Full name</label>
                        <input id="name" v-model="form.name" type="text" required autofocus autocomplete="name" placeholder="Thandi Mokoena" />
                        <div v-if="form.errors.name" class="alert err" style="margin: 8px 0 0">{{ form.errors.name }}</div>
                    </div>

                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" v-model="form.email" type="email" required autocomplete="email" placeholder="you@example.co.za" />
                        <div v-if="form.errors.email" class="alert err" style="margin: 8px 0 0">{{ form.errors.email }}</div>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" v-model="form.password" type="password" required autocomplete="new-password" placeholder="••••••••" />
                        <div v-if="form.errors.password" class="alert err" style="margin: 8px 0 0">{{ form.errors.password }}</div>
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                    </div>

                    <button class="btn" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating account…' : 'Create account' }}
                    </button>
                </form>

                <div class="alt">
                    Already have an account?
                    <Link class="link" :href="route('login')">Sign in</Link>
                </div>

                <div class="hint">
                    <b>Note</b><br />
                    For security, public registration can never create administrator accounts. Admins add cashiers from Store settings.
                </div>
            </div>
        </main>
    </div>
</template>
