<script setup lang="ts">
import Fab from '@/components/Fab.vue';
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

const props = defineProps<{ uuid: string }>();

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
    completed?: boolean; // for todos
    items?: any[]; // for checklists
    name?: string; // for folders
    color?: string; // for folders
    uuid?: string; // for folders
    x: number;
    y: number;
    width: number;
    height: number;
    created_at: string;
    updated_at: string;
}

const items = ref<Item[]>([]);
const loading = ref(true);

// Edit state for items (not needed with specific components)
// const editingItems = ref<Set<number>>(new Set());
// const editForms = ref<Record<number, { type: string; title: string; description: string }>>({});

const fetchItems = async () => {
    try {
        const response = await axios.get(`/api/boards/${props.uuid}/items${window.location.search || ''}`);
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

// One-click item creation functions with default configurations
const createQuickTodo = async () => {
    try {
        // Request server-side creation with defaults
        const response = await axios.post('/api/items', {
            type: 'todo',
            board_uuid: props.uuid,
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
            board_uuid: props.uuid,
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
            board_uuid: props.uuid,
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

const openFolder = (folderUuid: string) => {
    // Navigate to the same board with folder query parameter `f`
    window.location.assign(`/board/${props.uuid}?f=${encodeURIComponent(folderUuid)}`);
};

onMounted(() => {
    fetchItems();
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
            <div class="relative h-full w-full flex-1">
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
                    class="rounded-box"
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
                        :uuid="item.uuid"
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
        <Fab
            @action-a="createQuickTodo"
            @action-b="createQuickChecklist"
            @action-c="createQuickFolder"
        >
            <template #main>+</template>
            <template #action-a>📝</template>
            <template #action-b>✅</template>
            <template #action-c>📁</template>
        </Fab>
    </AppLayout>
</template>
