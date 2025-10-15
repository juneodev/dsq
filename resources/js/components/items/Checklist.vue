<script setup lang="ts">
import { computed, ref } from 'vue';
import { Trash } from 'lucide-vue-next';

interface ChecklistItem {
    text: string;
    completed: boolean;
}

interface ChecklistProps {
    id: number;
    title: string;
    description?: string;
    items: ChecklistItem[];
    x: number;
    y: number;
    width: number;
    height: number;
}

const props = defineProps<ChecklistProps>();

const emit = defineEmits<{
    update: [data: Partial<ChecklistProps>];
    delete: [id: number];
}>();

const newItemText = ref('');

const completedCount = computed(() =>
    props.items.filter(item => item.completed).length
);

const progressPercentage = computed(() =>
    props.items.length > 0 ? Math.round((completedCount.value / props.items.length) * 100) : 0
);

const addItem = () => {
    if (!newItemText.value.trim()) return;

    const updatedItems = [...props.items, { text: newItemText.value, completed: false }];
    emit('update', { items: updatedItems });
    newItemText.value = '';
};

const toggleItem = (index: number) => {
    const updatedItems = [...props.items];
    updatedItems[index].completed = !updatedItems[index].completed;
    emit('update', { items: updatedItems });
};

const removeItem = (index: number) => {
    const updatedItems = props.items.filter((_, i) => i !== index);
    emit('update', { items: updatedItems });
};
</script>

<template>
    <div class="h-full flex flex-col bg-white rounded-lg shadow-md border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded">
                    CHECKLIST
                </span>
                <h3 class="font-semibold text-lg text-gray-800">
                    {{ title }}
                </h3>
            </div>
        </div>

        <p v-if="description" class="text-gray-600 mb-2 text-sm">
            {{ description }}
        </p>

        <!-- Progress bar -->
        <div class="mb-3">
            <div class="flex justify-between text-xs text-gray-600 mb-1">
                <span>Progress</span>
                <span>{{ completedCount }}/{{ items.length }} ({{ progressPercentage }}%)</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                    class="bg-green-500 h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${progressPercentage}%` }"
                ></div>
            </div>
        </div>

        <!-- Checklist items -->
        <div class="flex-1 overflow-y-auto space-y-1 mb-2">
            <div
                v-for="(item, index) in items"
                :key="index"
                class="flex items-center gap-2 p-1 hover:bg-gray-50 rounded"
            >
                <input
                    :checked="item.completed"
                    @change="toggleItem(index)"
                    type="checkbox"
                    class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                />
                <span
                    :class="[
                        'flex-1 text-sm',
                        item.completed ? 'line-through text-gray-500' : 'text-gray-800'
                    ]"
                >
                    {{ item.text }}
                </span>
                <button
                    @click="removeItem(index)"
                    class="text-red-400 hover:text-red-600 text-xs"
                    title="Remove item"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Add new item -->
        <div class="flex gap-1 mt-auto">
            <input
                v-model="newItemText"
                @keyup.enter="addItem"
                type="text"
                placeholder="Add new item..."
                class="flex-1 px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-purple-500"
            />
            <button
                @click="addItem"
                :disabled="!newItemText.trim()"
                class="px-2 py-1 bg-purple-600 text-white text-xs rounded hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
            >
                +
            </button>
        </div>

        <div class="flex items-center justify-between mt-2">
            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">
                {{ items.length }} items
            </span>
            <button
                @click="$emit('delete', id)"
                class="btn btn-square btn-soft btn-sm btn-error"
                title="Delete checklist"
            >
                <Trash class="size-5" />
            </button>
        </div>
    </div>
</template>
