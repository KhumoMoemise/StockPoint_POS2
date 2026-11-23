<script setup lang="ts">
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        data: Array<{ label: string; value: number }>;
        currency?: boolean;
    }>(),
    { currency: true },
);

const hover = ref<number | null>(null);

const width = 400;
const height = 140;
const pad = { top: 14, right: 12, bottom: 22, left: 8 };

const max = computed(() => Math.max(1, ...props.data.map((d) => d.value)));
const hasData = computed(() => props.data.some((d) => d.value > 0));

const points = computed(() =>
    props.data.map((item, index) => {
        const step = props.data.length > 1 ? index / (props.data.length - 1) : 0;
        return {
            x: pad.left + (width - pad.left - pad.right) * step,
            y: height - pad.bottom - ((height - pad.top - pad.bottom) * item.value) / max.value,
            ...item,
        };
    }),
);

const linePath = computed(() => points.value.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x},${p.y}`).join(' '));
const areaPath = computed(() =>
    points.value.length
        ? `${linePath.value} L${points.value[points.value.length - 1].x},${height - pad.bottom} L${points.value[0].x},${height - pad.bottom} Z`
        : '',
);

function fmt(value: number): string {
    return props.currency ? 'R ' + value.toFixed(2) : String(value);
}
</script>

<template>
    <div class="sp-line-wrap">
        <div v-if="!hasData" class="sp-chart-empty">No data recorded for this period yet.</div>
        <svg v-else :viewBox="`0 0 ${width} ${height}`" preserveAspectRatio="none">
            <defs>
                <linearGradient id="spLineFill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#2563eb" stop-opacity=".22" />
                    <stop offset="1" stop-color="#2563eb" stop-opacity="0" />
                </linearGradient>
            </defs>
            <path :d="areaPath" fill="url(#spLineFill)" />
            <path :d="linePath" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
            <circle
                v-for="(point, index) in points"
                :key="index"
                :cx="point.x"
                :cy="point.y"
                r="3.5"
                :class="['sp-line-dot', { 'is-hover': hover === index }]"
                @mouseenter="hover = index"
                @mouseleave="hover = null"
            >
                <title>{{ point.label }} · {{ fmt(point.value) }}</title>
            </circle>
        </svg>
    </div>
</template>
