<script setup lang="ts">
import { KanbanSquareDashed } from 'lucide-vue-next';
</script>

<template>
    <div class="gpa-3 flex justify-center items-center">
        <KanbanSquareDashed class="text-primary h-6 w-6 flex-shrink-0" />
        <div class="ml-1 grid flex-1 text-left text-sm">
            <span class="truncate leading-tight font-semibold">DoBoard</span>
        </div>
    </div>
</template>
