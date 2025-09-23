<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface Board {
    id: number;
    uuid?: string;
    title: string;
    description?: string | null;
    created_at: string;
    updated_at: string;
}

const boards = ref<Board[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// Create form state
const newTitle = ref('');
const newDescription = ref('');
const creating = ref(false);

// Edit state
const editingId = ref<number | null>(null);
const editTitle = ref('');
const editDescription = ref('');

const startEdit = (board: Board) => {
    editingId.value = board.id;
    editTitle.value = board.title;
    editDescription.value = board.description ?? '';
};
const cancelEdit = () => {
    editingId.value = null;
    editTitle.value = '';
    editDescription.value = '';
};

const fetchBoards = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/boards');
        boards.value = response.data;
    } catch (e: any) {
        console.error('Error fetching boards:', e);
        error.value = 'Failed to load your boards.';
    } finally {
        loading.value = false;
    }
};

const createBoard = async () => {
    if (!newTitle.value.trim()) return;
    creating.value = true;
    try {
        const response = await axios.post('/api/boards', {
            title: newTitle.value.trim(),
            description: newDescription.value.trim() || null,
        });
        boards.value.unshift(response.data);
        newTitle.value = '';
        newDescription.value = '';
    } catch (e) {
        console.error('Error creating board', e);
    } finally {
        creating.value = false;
    }
};

const saveBoard = async (boardId: number) => {
    try {
        const response = await axios.put(`/api/boards/${boardId}`, {
            title: editTitle.value.trim(),
            description: editDescription.value.trim() || null,
        });
        const idx = boards.value.findIndex(b => b.id === boardId);
        if (idx !== -1) boards.value[idx] = response.data;
        cancelEdit();
    } catch (e) {
        console.error('Error updating board', e);
    }
};

const deleteBoard = async (boardId: number) => {
    try {
        await axios.delete(`/api/boards/${boardId}`);
        boards.value = boards.value.filter(b => b.id !== boardId);
    } catch (e) {
        console.error('Error deleting board', e);
    }
};

onMounted(fetchBoards);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-5xl p-4">
            <h1 class="mb-4 text-2xl font-semibold">Your boards</h1>

            <!-- Create form -->
            <div class="mb-6 rounded-box border border-base-200 bg-base-100 p-4">
                <div class="mb-2 text-sm font-medium text-gray-700">Create a new board</div>
                <div class="flex flex-col gap-2 md:flex-row">
                    <input v-model="newTitle" type="text" class="input input-bordered flex-1" placeholder="Board title" />
                    <input v-model="newDescription" type="text" class="input input-bordered flex-1" placeholder="Description (optional)" />
                    <button class="btn btn-primary" :disabled="creating || !newTitle.trim()" @click="createBoard">
                        {{ creating ? 'Creating...' : 'Create' }}
                    </button>
                </div>
            </div>

            <div v-if="loading" class="p-4 text-center">Loading boards...</div>
            <div v-else-if="error" class="p-4 text-center text-red-600">{{ error }}</div>
            <div v-else>
                <div v-if="boards.length === 0" class="p-8 text-center text-gray-500">
                    You have no boards yet.
                </div>
                <ul v-else class="grid gap-4 md:grid-cols-2">
                    <li
                        v-for="board in boards"
                        :key="board.id"
                        class="rounded-box border border-base-200 bg-base-100 p-4 shadow-sm hover:shadow-md"
                    >
                        <div class="flex flex-col gap-2">
                            <template v-if="editingId === board.id">
                                <input v-model="editTitle" type="text" class="input input-bordered" placeholder="Title" />
                                <input v-model="editDescription" type="text" class="input input-bordered" placeholder="Description (optional)" />
                                <div class="mt-2 flex gap-2">
                                    <button class="btn btn-success btn-sm" @click="saveBoard(board.id)">Save</button>
                                    <button class="btn btn-ghost btn-sm" @click="cancelEdit">Cancel</button>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h2 class="text-lg font-medium">{{ board.title }}</h2>
                                        <p v-if="board.description" class="text-sm text-gray-600">
                                            {{ board.description }}
                                        </p>
                                        <p class="mt-2 text-xs text-gray-400">
                                            Updated {{ new Date(board.updated_at).toLocaleString() }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <Link class="btn btn-sm btn-primary" :href="`/board/${board.uuid}`">Open</Link>
                                        <button class="btn btn-sm" @click="startEdit(board)">Edit</button>
                                        <button class="btn btn-sm btn-error" @click="deleteBoard(board.id)">Delete</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
