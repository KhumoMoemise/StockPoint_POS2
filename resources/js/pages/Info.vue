<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    body: string;
}>();
</script>

<template>
    <div class="sp-info">
        <Head :title="title" />

        <header>
            <div class="wrap">
                <Link class="brand" :href="route('home')"><span class="brand-mark">▦</span> StockPoint</Link>
            </div>
        </header>

        <div class="wrap">
            <article class="card">
                <h1>{{ title }}</h1>
                <p>{{ body }}</p>
                <Link class="back" :href="route('home')">← Back to home</Link>
            </article>
        </div>
    </div>
</template>
