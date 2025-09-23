<script setup lang="ts">
import Checklist from '@/components/items/Checklist.vue';
import Folder from '@/components/items/Folder.vue';
import Todo from '@/components/items/Todo.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import DraggableResizable from 'draggable-resizable-vue3';
import { onMounted, ref } from 'vue';
import Fab from '@/components/Fab.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface Item {
    id: number;
    type: string;
    title: string;
    description?: string;
    completed: boolean;
    x: number;
    y: number;
    width: number;
    height: number;
    created_at: string;
    updated_at: string;
}

const items = ref<Item[]>([]);
const loading = ref(true);

// Form state for creating new items
const showCreateForm = ref(false);
const newItem = ref({
    type: 'todo',
    title: '',
    description: '',
});

// Edit state for items (not needed with specific components)
// const editingItems = ref<Set<number>>(new Set());
// const editForms = ref<Record<number, { type: string; title: string; description: string }>>({});

const fetchItems = async () => {
    try {
        const response = await axios.get('/api/items');
        items.value = response.data;
    } catch (error) {
        console.error('Error fetching items:', error);
    } finally {
        loading.value = false;
    }
};

const updateItemPosition = async (item: Item) => {
    try {
        await axios.put(`/api/items/${item.id}`, {
            x: item.x,
            y: item.y,
            width: item.width,
            height: item.height,
        });
    } catch (error) {
        console.error('Error updating item position:', error);
    }
};

const createItem = async () => {
    if (!newItem.value.title.trim()) return;

    try {
        const response = await axios.post('/api/items', {
            type: newItem.value.type,
            title: newItem.value.title,
            description: newItem.value.description,
            x: Math.floor(Math.random() * 300),
            y: Math.floor(Math.random() * 200),
            width: 250,
            height: 150,
        });

        items.value.push(response.data);
        newItem.value.title = '';
        newItem.value.description = '';
        newItem.value.type = 'todo';
        showCreateForm.value = false;
    } catch (error) {
        console.error('Error creating item:', error);
    }
};

// One-click item creation functions with default configurations
const createQuickTodo = async () => {
    try {
        const response = await axios.post('/api/items', {
            type: 'todo',
            title: 'New Todo',
            description: 'Click to edit description',
            x: Math.floor(Math.random() * 300),
            y: Math.floor(Math.random() * 200),
            width: 250,
            height: 150,
        });

        items.value.push(response.data);
    } catch (error) {
        console.error('Error creating todo:', error);
    }
};

const createQuickChecklist = async () => {
    try {
        const response = await axios.post('/api/items', {
            type: 'checklist',
            title: 'New Checklist',
            description: 'Click to add items to your checklist',
            items: [],
            x: Math.floor(Math.random() * 300),
            y: Math.floor(Math.random() * 200),
            width: 280,
            height: 200,
        });

        items.value.push(response.data);
    } catch (error) {
        console.error('Error creating checklist:', error);
    }
};

const createQuickFolder = async () => {
    try {
        const response = await axios.post('/api/items', {
            type: 'folder',
            name: 'New Folder',
            description: 'Organize your items here',
            color: '#3b82f6',
            x: Math.floor(Math.random() * 300),
            y: Math.floor(Math.random() * 200),
            width: 240,
            height: 180,
        });

        items.value.push(response.data);
    } catch (error) {
        console.error('Error creating folder:', error);
    }
};

const deleteItem = async (id: number) => {
    if (!confirm('Are you sure you want to delete this item?')) return;

    try {
        await axios.delete(`/api/items/${id}`);
        items.value = items.value.filter((item) => item.id !== id);
    } catch (error) {
        console.error('Error deleting item:', error);
    }
};

// Edit functions removed - now handled by individual item components

const updateItem = async (itemId: number, data: any) => {
    try {
        const response = await axios.put(`/api/items/${itemId}`, data);

        const itemIndex = items.value.findIndex((item) => item.id === itemId);
        if (itemIndex !== -1) {
            items.value[itemIndex] = {
                ...items.value[itemIndex],
                ...response.data,
            };
        }
    } catch (error) {
        console.error('Error updating item:', error);
    }
};

const openFolder = (folderId: number) => {
    console.log('Opening folder:', folderId);
    // TODO: Implement folder opening logic
};

onMounted(() => {
    fetchItems();
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Create Item Section -->
            <div
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-md"
            >
                <div v-if="!showCreateForm" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">
                            Items Dashboard
                        </h2>
                        <button
                            @click="showCreateForm = true"
                            class="rounded-lg bg-gray-600 px-4 py-2  transition-colors hover:bg-gray-700"
                        >
                            + Custom Item
                        </button>
                    </div>

                    <!-- Quick Creation Buttons -->
                    <div class="flex flex-wrap gap-3">
                        <button
                            @click="createQuickTodo"
                            class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2  transition-colors hover:bg-blue-700"
                        >
                            📝 Quick Todo
                        </button>
                        <button
                            @click="createQuickChecklist"
                            class="flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2  transition-colors hover:bg-purple-700"
                        >
                            ✅ Quick Checklist
                        </button>
                        <button
                            @click="createQuickFolder"
                            class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2  transition-colors hover:bg-green-700"
                        >
                            📁 Quick Folder
                        </button>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Create New Item
                        </h3>
                        <button
                            @click="
                                showCreateForm = false;
                                newItem.title = '';
                                newItem.description = '';
                                newItem.type = 'todo';
                            "
                            class="text-gray-500 hover:text-gray-700"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <select
                            v-model="newItem.type"
                            class="rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        >
                            <option value="todo">Todo</option>
                            <option value="checklist">Checklist</option>
                            <option value="folder">Folder</option>
                        </select>
                        <input
                            v-model="newItem.title"
                            type="text"
                            placeholder="Item title"
                            class="rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            @keyup.enter="createItem"
                        />
                        <input
                            v-model="newItem.description"
                            type="text"
                            placeholder="Description (optional)"
                            class="rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            @keyup.enter="createItem"
                        />
                    </div>

                    <div class="flex space-x-2">
                        <button
                            @click="createItem"
                            :disabled="!newItem.title.trim()"
                            class="rounded-lg bg-green-600 px-4 py-2  transition-colors hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                        >
                            Create
                        </button>
                        <button
                            @click="
                                showCreateForm = false;
                                newItem.title = '';
                                newItem.description = '';
                                newItem.type = 'todo';
                            "
                            class="rounded-lg bg-gray-500 px-4 py-2  transition-colors hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="relative h-full w-full flex-1 rounded-lg border bg-stone-50 p-4"
            >
                <div v-if="loading" class="p-4 text-center">
                    Loading items...
                </div>

                <div v-else-if="items.length === 0" class="p-4 text-center">
                    No items found. Create some items to see them here!
                </div>

                <DraggableResizable
                    v-for="item in items"
                    :key="item.id"
                    v-model:x="item.x"
                    v-model:y="item.y"
                    v-model:w="item.width"
                    :grid="[20, 20]"
                    :show-grid="true"
                    :parent="true"
                    @dragstop="updateItemPosition(item)"
                    @resize-stop="updateItemPosition(item)"
                >
                    <!-- Render specific item component based on type -->
                    <Todo
                        v-if="item.type === 'todo'"
                        :id="item.id"
                        :title="item.title"
                        :description="item.description"
                        :completed="item.completed"
                        :x="item.x"
                        :y="item.y"
                        :width="item.width"
                        :height="item.height"
                        @update="updateItem(item.id, $event)"
                        @delete="deleteItem"
                    />
                    <Checklist
                        v-else-if="item.type === 'checklist'"
                        :id="item.id"
                        :title="item.title"
                        :description="item.description"
                        :items="item.items || []"
                        :x="item.x"
                        :y="item.y"
                        :width="item.width"
                        :height="item.height"
                        @update="updateItem(item.id, $event)"
                        @delete="deleteItem"
                    />
                    <Folder
                        v-else-if="item.type === 'folder'"
                        :id="item.id"
                        :name="item.name"
                        :description="item.description"
                        :color="item.color"
                        :x="item.x"
                        :y="item.y"
                        :width="item.width"
                        :height="item.height"
                        @update="updateItem(item.id, $event)"
                        @delete="deleteItem"
                        @open="openFolder"
                    />
                </DraggableResizable>
            </div>
        </div>
        <Fab />
    </AppLayout>
</template>
