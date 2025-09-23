<script setup lang="ts">
import { KanbanSquareDashed } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <span
        :class="className"
        style="display: inline-flex; gap: 8px"
        v-bind="$attrs"
    >
        <KanbanSquareDashed class="h-8 w-8 flex-shrink-0" />
    </span>
</template>
