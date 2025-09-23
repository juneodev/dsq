<script setup lang="ts">
import { computed } from 'vue';

interface TodoProps {
    id: number;
    title: string;
    description?: string;
    completed: boolean;
    x: number;
    y: number;
    width: number;
    height: number;
}

const props = defineProps<TodoProps>();

defineEmits<{
    update: [data: Partial<TodoProps>];
    delete: [id: number];
}>();

const statusClass = computed(() =>
    props.completed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
);
</script>

<template>
    <div class="h-full flex flex-col bg-white rounded-lg shadow-md border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">
                    TODO
                </span>
                <h3 class="font-semibold text-lg text-gray-800">
                    {{ title }}
                </h3>
            </div>
            <div class="flex gap-1">
                <button
                    @click="$emit('update', { completed: !completed })"
                    :class="[
                        'p-1 rounded transition-colors',
                        completed
                            ? 'text-yellow-600 hover:text-yellow-800 hover:bg-yellow-100'
                            : 'text-green-600 hover:text-green-800 hover:bg-green-100'
                    ]"
                    :title="completed ? 'Mark as pending' : 'Mark as completed'"
                >
                    {{ completed ? '↩️' : '✅' }}
                </button>
                <button
                    @click="$emit('delete', id)"
                    class="text-red-500 hover:text-red-700 hover:bg-red-100 p-1 rounded transition-colors"
                    title="Delete todo"
                >
                    🗑️
                </button>
            </div>
        </div>

        <p v-if="description" class="text-gray-600 mb-2 flex-1 text-sm">
            {{ description }}
        </p>

        <div class="flex items-center justify-between mt-auto">
            <span :class="['px-2 py-1 rounded text-xs font-medium', statusClass]">
                {{ completed ? 'Completed' : 'Pending' }}
            </span>
            <span class="text-xs text-gray-400">
                ID: {{ id }}
            </span>
        </div>
    </div>
</template>
