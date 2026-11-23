<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
    wide?: boolean;
}>();

const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <div class="modal-bg" @click.self="emit('close')">
        <div class="modal" :class="{ wide }">
            <h3>{{ title }}</h3>
            <p v-if="subtitle">{{ subtitle }}</p>
            <slot />
        </div>
    </div>
</template>
