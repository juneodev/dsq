<script setup lang="ts">
import { File } from 'lucide-vue-next';

interface Item {
    id: number;
    type: string;
    title: string;
    description?: string;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{ items: Item[] }>();
</script>

<template>
    <div>
        <div class="border-b border-base-300 p-3 text-sm font-semibold">
            Liste des éléments
        </div>
        <ul class="divide-y divide-base-200">
            <li
                v-for="it in props.items"
                :key="it.id"
                class="flex items-start gap-3 p-3"
            >
                <div class="min-w-0 flex-1">
                    <File></File>
                    <div class="truncate font-medium">{{ it.title }}</div>
                </div>
                <div class="text-xs whitespace-nowrap text-base-content/60">
                    #{{ it.id }}
                </div>
            </li>
        </ul>
    </div>
</template>
