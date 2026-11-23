<script setup lang="ts">
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        data: Array<{ label: string; value: number }>;
        color?: string;
        currency?: boolean;
    }>(),
    { color: '#2563eb', currency: true },
);

const hover = ref<number | null>(null);

const max = computed(() => Math.max(1, ...props.data.map((d) => d.value)));
const hasData = computed(() => props.data.some((d) => d.value > 0));

function fmt(value: number): string {
    return props.currency ? 'R ' + value.toFixed(2) : String(value);
}
</script>

<template>
    <div class="sp-chart">
        <div v-if="!hasData" class="sp-chart-empty">No data recorded for this period yet.</div>
        <div
            v-for="(item, index) in data"
            v-else
            :key="index"
            class="sp-chart-col"
            @mouseenter="hover = index"
            @mouseleave="hover = null"
        >
            <div v-if="hover === index" class="sp-chart-tip">{{ item.label }} · {{ fmt(item.value) }}</div>
            <div class="sp-chart-bar" :style="{ height: (item.value / max) * 100 + '%', background: color }"></div>
            <span class="sp-chart-label">{{ item.label }}</span>
        </div>
    </div>
</template>
