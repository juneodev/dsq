<script setup lang="ts">
import Fab from '@/components/Fab.vue';
import Checklist from '@/components/items/Checklist.vue';
import Folder from '@/components/items/Folder.vue';
import Todo from '@/components/items/Todo.vue';
import Note from '@/components/items/Note.vue';
import Bookmark from '@/components/items/Bookmark.vue';
import EventCard from '@/components/items/EventCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import DraggableResizable from 'draggable-resizable-vue3';
import { onMounted, onBeforeUnmount, ref } from 'vue';
import { Plus, ListTodo, CheckSquare, Folder as FolderIcon, StickyNote, Link as LinkIcon, Calendar } from 'lucide-vue-next';

const props = defineProps<{ uuid: string; breadcrumbs?: BreadcrumbItem[] }>();

const breadcrumbItems: BreadcrumbItem[] = props.breadcrumbs ?? [
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

// Board viewport and panning state
const viewportEl = ref<HTMLElement | null>(null);
const boardContainer = ref<HTMLElement | null>(null);
const BOARD_WIDTH = 4000;
const BOARD_HEIGHT = 3000;
const panX = ref(0);
const panY = ref(0);
const isPanning = ref(false);
let startClientX = 0;
let startClientY = 0;
let startPanX = 0;
let startPanY = 0;

const clamp = (val: number, min: number, max: number) => Math.max(min, Math.min(max, val));

const computePanBounds = () => {
    const boardEl = boardContainer.value;
    const boardW = boardEl?.offsetWidth ?? BOARD_WIDTH;
    const boardH = boardEl?.offsetHeight ?? BOARD_HEIGHT;

    // Use the real viewport size from the window to avoid inflated container heights
    const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
    const vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);

    const minX = Math.min(0, vw - boardW);
    const minY = Math.min(0, vh - boardH);
    const bounds = { minX, maxX: 0, minY, maxY: 0 } as const;
    return bounds as { minX: number; maxX: number; minY: number; maxY: number };
};

const onPanMove = (e: MouseEvent) => {
    if (!isPanning.value) return;
    e.preventDefault();
    const { minX, maxX, minY, maxY } = computePanBounds();
    const dx = e.clientX - startClientX;
    const dy = e.clientY - startClientY;
    const nextX = clamp(startPanX + dx, minX, maxX);
    const nextY = clamp(startPanY + dy, minY, maxY);
    panX.value = nextX;
    panY.value = nextY;
};

const endPan = () => {
    if (!isPanning.value) return;
    isPanning.value = false;
    window.removeEventListener('mousemove', onPanMove);
    window.removeEventListener('mouseup', endPan);
};

// Start panning only when clicking on the empty board background
const startPan = (e: MouseEvent) => {
    // Only start if the target is the surface element itself
    const target = e.target as HTMLElement;
    if (!target || !target.classList.contains('pan-surface')) {
        return;
    }
    e.preventDefault();
    isPanning.value = true;
    startClientX = e.clientX;
    startClientY = e.clientY;
    startPanX = panX.value;
    startPanY = panY.value;
    window.addEventListener('mousemove', onPanMove);
    window.addEventListener('mouseup', endPan);
};

// Drag and drop folder hit-test helpers
const folderEls = ref(new Map<number, HTMLElement>());

const registerFolderEl = (itemId: number) => (el: HTMLElement | null) => {
    if (!el) {
        folderEls.value.delete(itemId);
    } else {
        folderEls.value.set(itemId, el);
    }
};

const hitTestFolder = (item: Item): string | null => {
    const container = boardContainer.value;
    if (!container) {
        return null;
    }

    const containerRect = container.getBoundingClientRect();
    const centerX = containerRect.left + item.x + (item.width ?? 0) / 2;
    const centerY = containerRect.top + item.y + (item.height ?? 0) / 2;

    let found: string | null = null;
    for (const [folderItemId, el] of folderEls.value.entries()) {
        if (folderItemId === item.id) {
            continue;
        }

        const r = el.getBoundingClientRect();
        const within = centerX >= r.left && centerX <= r.right && centerY >= r.top && centerY <= r.bottom;
        if (within) {
            const folder = items.value.find(i => i.id === folderItemId);
            if (folder && folder.type === 'folder' && folder.uuid) {
                if (item.type === 'folder' && (item as any).uuid && folder.uuid === (item as any).uuid) {
                    continue;
                }
                found = folder.uuid;
                break;
            }
        }
    }
    return found;
};

const onDragStop = async (item: Item) => {
    await updateItemPosition(item);
    const targetUuid = hitTestFolder(item);
    const params = new URLSearchParams(window.location.search);
    const currentFolder = params.get('f');

    if (targetUuid) {
        await updateItem(item.id, { folder_uuid: targetUuid });
        // Optimistic UI update: remove the item from the current list since it moved into a folder
        items.value = items.value.filter((i) => i.id !== item.id);
    } else if (currentFolder) {
        // Inside a folder view and no folder hit: keep item in the current folder; only position was updated above.
        // No folder_uuid change here.
    } else {
    }
};

// Edit state for items (not needed with specific components)
// const editingItems = ref<Set<number>>(new Set());
// const editForms = ref<Record<number, { type: string; title: string; description: string }>>({});

const fetchItems = async () => {
    try {
        const response = await axios.get(`/api/boards/${props.uuid}/items${window.location.search || ''}`);
        items.value = response.data;
    } catch (error) {
        console.error('[Board] Error fetching items:', error);
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
        console.error('[Board] Error updating item position:', error);
    }
};

// One-click item creation functions with default configurations
const createQuickTodo = async () => {
    try {
        const params = new URLSearchParams(window.location.search);
        const folderUuid = params.get('f');
        // Request server-side creation with defaults
        const response = await axios.post('/api/items', {
            type: 'todo',
            board_uuid: props.uuid,
            folder_uuid: folderUuid || undefined,
        });

        items.value.push(response.data);
    } catch (error) {
        console.error('Error creating todo:', error);
    }
};

const createQuickChecklist = async () => {
    try {
        const params = new URLSearchParams(window.location.search);
        const folderUuid = params.get('f');
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
            folder_uuid: folderUuid || undefined,
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

const createQuickNote = async () => {
    try {
        const params = new URLSearchParams(window.location.search);
        const folderUuid = params.get('f');
        const response = await axios.post('/api/items', {
            type: 'note',
            title: 'New Note',
            content: '',
            color: '#FEF3C7',
            pinned: false,
            x: Math.floor(Math.random() * 300),
            y: Math.floor(Math.random() * 200),
            width: 320,
            height: 200,
            board_uuid: props.uuid,
            folder_uuid: folderUuid || undefined,
        });
        items.value.push(response.data);
    } catch (error) {
        console.error('Error creating note:', error);
    }
};

const createQuickBookmark = async () => {
    try {
        const params = new URLSearchParams(window.location.search);
        const folderUuid = params.get('f');
        const response = await axios.post('/api/items', {
            type: 'bookmark',
            title: 'New Link',
            url: 'https://example.com',
            favicon_url: null,
            tags: [],
            x: Math.floor(Math.random() * 300),
            y: Math.floor(Math.random() * 200),
            width: 260,
            height: 120,
            board_uuid: props.uuid,
            folder_uuid: folderUuid || undefined,
        });
        items.value.push(response.data);
    } catch (error) {
        console.error('Error creating bookmark:', error);
    }
};

const createQuickEvent = async () => {
    try {
        const params = new URLSearchParams(window.location.search);
        const folderUuid = params.get('f');
        const response = await axios.post('/api/items', {
            type: 'event',
            title: 'New Event',
            start_at: new Date().toISOString(),
            end_at: null,
            location: null,
            all_day: false,
            remind_minutes_before: null,
            x: Math.floor(Math.random() * 300),
            y: Math.floor(Math.random() * 200),
            width: 280,
            height: 140,
            board_uuid: props.uuid,
            folder_uuid: folderUuid || undefined,
        });
        items.value.push(response.data);
    } catch (error) {
        console.error('Error creating event:', error);
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
        console.error('[Board] Error updating item:', error);
    }
};

const openFolder = (folderUuid: string) => {
    // Navigate to the same board with folder query parameter `f`
    window.location.assign(`/board/${props.uuid}?f=${encodeURIComponent(folderUuid)}`);
};

let prevBodyOverflow: string | null = null;

onMounted(() => {
    // Lock body scroll to avoid vertical scrollbar interfering with panning
    prevBodyOverflow = document.body.style.overflow || '';
    document.body.style.overflow = 'hidden';
    fetchItems();
});

onBeforeUnmount(() => {
    console.log('[Board] onBeforeUnmount');
    endPan();
    // Restore body scroll
    if (prevBodyOverflow !== null) {
        document.body.style.overflow = prevBodyOverflow;
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-hidden">
            <div class="relative h-full w-full flex-1 overflow-hidden" ref="viewportEl">
                <div v-if="loading" class="p-4 text-center">
                    Loading items...
                </div>

                <div v-else-if="items.length === 0" class="p-4 text-center">
                    No items found. Create some items to see them here!
                </div>

                <div
                    ref="boardContainer"
                    class="relative select-none"
                    :style="{ width: BOARD_WIDTH + 'px', height: BOARD_HEIGHT + 'px', transform: `translate(${panX}px, ${panY}px)` }"
                >
                    <!-- Transparent surface to initiate panning when clicking empty board space -->
                    <div
                        class="pan-surface absolute inset-0 z-0 cursor-grab"
                        @mousedown="startPan"
                        :class="{ 'cursor-grabbing': isPanning }"
                    ></div>

                    <DraggableResizable
                        v-for="item in items"
                        :key="item.id"
                        v-model:x="item.x"
                        v-model:y="item.y"
                        v-model:w="item.width"
                        :grid="[20, 20]"
                        :show-grid="true"
                        :parent="true"
                        @dragstop="onDragStop(item)"
                        @resize-stop="updateItemPosition(item)"
                        class="rounded-box z-10"
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
                        <template v-else-if="item.type === 'folder'">
                            <div :ref="registerFolderEl(item.id)">
                                <Folder
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
                            </div>
                        </template>
                        <Note
                            v-else-if="item.type === 'note'"
                            :id="item.id"
                            :title="item.title"
                            :content="item.content"
                            :color="item.color"
                            :pinned="item.pinned"
                            :x="item.x"
                            :y="item.y"
                            :width="item.width"
                            :height="item.height"
                            @update="updateItem(item.id, $event)"
                            @delete="deleteItem"
                        />
                        <Bookmark
                            v-else-if="item.type === 'bookmark'"
                            :id="item.id"
                            :title="item.title"
                            :url="item.url"
                            :favicon_url="item.favicon_url"
                            :tags="item.tags || []"
                            :x="item.x"
                            :y="item.y"
                            :width="item.width"
                            :height="item.height"
                            @update="updateItem(item.id, $event)"
                            @delete="deleteItem"
                        />
                        <EventCard
                            v-else-if="item.type === 'event'"
                            :id="item.id"
                            :title="item.title"
                            :start_at="item.start_at"
                            :end_at="item.end_at"
                            :location="item.location"
                            :all_day="item.all_day"
                            :remind_minutes_before="item.remind_minutes_before"
                            :x="item.x"
                            :y="item.y"
                            :width="item.width"
                            :height="item.height"
                            @update="updateItem(item.id, $event)"
                            @delete="deleteItem"
                        />
                    </DraggableResizable>
                </div>
            </div>
        </div>
        <Fab
            @action-a="createQuickTodo"
            @action-b="createQuickChecklist"
            @action-c="createQuickFolder"
            @action-d="createQuickNote"
            @action-e="createQuickBookmark"
            @action-f="createQuickEvent"
        >
            <template #main>
                <Plus class="size-6" />
            </template>
            <template #action-a>
                <ListTodo class="size-6" />
            </template>
            <template #action-b>
                <CheckSquare class="size-6" />
            </template>
            <template #action-c>
                <FolderIcon class="size-6" />
            </template>
            <template #action-d>
                <StickyNote class="size-6" />
            </template>
            <template #action-e>
                <LinkIcon class="size-6" />
            </template>
            <template #action-f>
                <Calendar class="size-6" />
            </template>
        </Fab>
    </AppLayout>
</template>
