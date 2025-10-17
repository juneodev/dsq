<template>
    <div class="relative rounded-box border border-base-300 bg-white p-4">
        <slot />
    </div>
</template>

<script setup lang="ts">
// Simple container component for all board items.
// It only renders the default slot as its content.
// Usage: <Item>...item content...</Item>

defineOptions({ name: 'BoardCanvasItem' });
</script>
