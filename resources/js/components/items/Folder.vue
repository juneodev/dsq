<script setup lang="ts">
import { computed } from 'vue';

interface FolderProps {
    id: number;
    name: string;
    description?: string;
    color: string;
    x: number;
    y: number;
    width: number;
    height: number;
}

const props = defineProps<FolderProps & { uuid: string }>();

const emit = defineEmits<{
    update: [data: Partial<FolderProps>];
    delete: [id: number];
    open: [uuid: string];
}>();

const folderStyle = computed(() => ({
    backgroundColor: props.color + '20', // Add transparency
    borderColor: props.color
}));

const colorOptions = [
    { name: 'Blue', value: '#3b82f6' },
    { name: 'Green', value: '#10b981' },
    { name: 'Red', value: '#ef4444' },
    { name: 'Yellow', value: '#f59e0b' },
    { name: 'Purple', value: '#8b5cf6' },
    { name: 'Pink', value: '#ec4899' },
    { name: 'Indigo', value: '#6366f1' },
    { name: 'Orange', value: '#f97316' }
];

const changeColor = (newColor: string) => {
    emit('update', { color: newColor });
};
</script>

<template>
    <div
        class="h-full flex flex-col rounded-lg shadow-md border-2 p-4 relative"
        :style="folderStyle"
    >
        <!-- Folder icon background -->
        <div class="absolute top-2 right-2 text-6xl opacity-10" :style="{ color: color }">
            📁
        </div>

        <div class="flex items-center justify-between mb-2 relative z-10">
            <div class="flex items-center gap-2">
                <span
                    class="px-2 py-1 text-white text-xs font-medium rounded"
                    :style="{ backgroundColor: color }"
                >
                    FOLDER
                </span>
                <h3 class="font-semibold text-lg text-gray-800">
                    {{ name }}
                </h3>
            </div>
            <div class="flex gap-1">
                <button
                    @click="$emit('open', uuid)"
                    class="text-blue-600 hover:text-blue-800 hover:bg-blue-100 p-1 rounded transition-colors"
                    title="Open folder"
                >
                    📂
                </button>
                <button
                    @click="$emit('delete', id)"
                    class="text-red-500 hover:text-red-700 hover:bg-red-100 p-1 rounded transition-colors"
                    title="Delete folder"
                >
                    🗑️
                </button>
            </div>
        </div>

        <p v-if="description" class="text-gray-700 mb-3 flex-1 text-sm relative z-10">
            {{ description }}
        </p>

        <!-- Color picker -->
        <div class="mt-auto relative z-10">
            <p class="text-xs text-gray-600 mb-2">Folder Color:</p>
            <div class="flex flex-wrap gap-1">
                <button
                    v-for="colorOption in colorOptions"
                    :key="colorOption.value"
                    @click="changeColor(colorOption.value)"
                    :class="[
                        'w-6 h-6 rounded-full border-2 hover:scale-110 transition-transform',
                        color === colorOption.value ? 'border-gray-800 border-4' : 'border-gray-300'
                    ]"
                    :style="{ backgroundColor: colorOption.value }"
                    :title="colorOption.name"
                />
            </div>
        </div>

        <div class="flex items-center justify-between mt-3 relative z-10">
            <span
                class="px-2 py-1 text-white rounded text-xs font-medium"
                :style="{ backgroundColor: color }"
            >
                Folder
            </span>
            <span class="text-xs text-gray-500">
                ID: {{ id }}
            </span>
        </div>
    </div>
</template>
