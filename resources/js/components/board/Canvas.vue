<script setup lang="ts">
import Fab from '@/components/Fab.vue';
import Checklist from '@/components/items/Checklist.vue';
import Folder from '@/components/items/Folder.vue';
import Todo from '@/components/items/Todo.vue';
import DocumentItem from '@/components/items/Document.vue';
import Note from '@/components/items/Note.vue';
import Bookmark from '@/components/items/Bookmark.vue';
import EventCard from '@/components/items/EventCard.vue';
import Item from '@/components/board/canvas/Item.vue';
import DraggableResizable from 'draggable-resizable-vue3';
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';
import { Plus, ListTodo, CheckSquare, Folder as FolderIcon, StickyNote, Link as LinkIcon, Calendar } from 'lucide-vue-next';

interface Item {
  id: number;
  type: string;
  title: string;
  description?: string;
  completed?: boolean;
  items?: any[];
  name?: string;
  color?: string;
  uuid?: string;
  x: number;
  y: number;
  width: number;
  height: number;
  created_at: string;
  updated_at: string;
}

const props = defineProps<{ uuid: string; items: Item[] }>();
const emit = defineEmits<{
  'update:items': [items: Item[]];
}>();

// Local items copy to manipulate and emit back
const itemsRef = ref<Item[]>([...props.items]);
watch(
  () => props.items,
  (val) => {
    // sync from parent when it changes externally
    itemsRef.value = [...val];
  }
);
const setItems = (next: Item[]) => {
  itemsRef.value = next;
  emit('update:items', next);
};

// Upload dropzone state
const isDragging = ref(false);
const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadError = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const onDragOver = (e: DragEvent) => {
  e.preventDefault();
  isDragging.value = true;
};
const onDragLeave = () => {
  isDragging.value = false;
};
const onDrop = async (e: DragEvent) => {
  e.preventDefault();
  isDragging.value = false;
  uploadError.value = null;
  const files = e.dataTransfer?.files;
  if (!files || files.length === 0) return;
  await startUpload(files[0]);
};
const onSelectClick = () => fileInputRef.value?.click();
const onFileSelected = async (e: Event) => {
  const input = e.target as HTMLInputElement;
  if (!input.files || input.files.length === 0) return;
  uploadError.value = null;
  await startUpload(input.files[0]);
  input.value = '';
};

const startUpload = async (file: File) => {
  const form = new FormData();
  form.append('file', file);
  isUploading.value = true;
  uploadProgress.value = 0;
  try {
    const response = await axios.post(`/api/boards/${props.uuid}/upload`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        if (!progressEvent.total) return;
        uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
      },
    });
    setItems([...itemsRef.value, response.data]);
  } catch (err: any) {
    console.error('Upload failed', err);
    uploadError.value = err?.response?.data?.message || 'Upload failed';
  } finally {
    isUploading.value = false;
    uploadProgress.value = 0;
  }
};

// Board viewport and panning state
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
  const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
  const vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
  const minX = Math.min(0, vw - boardW);
  const minY = Math.min(0, vh - boardH);
  return { minX, maxX: 0, minY, maxY: 0 } as const;
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

const startPan = (e: MouseEvent) => {
  const target = e.target as HTMLElement;
  if (!target || !target.classList.contains('pan-surface')) return;
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
  if (!el) folderEls.value.delete(itemId);
  else folderEls.value.set(itemId, el);
};

const hitTestFolder = (item: Item): string | null => {
  const container = boardContainer.value;
  if (!container) return null;
  const containerRect = container.getBoundingClientRect();
  const centerX = containerRect.left + item.x + (item.width ?? 0) / 2;
  const centerY = containerRect.top + item.y + (item.height ?? 0) / 2;
  let found: string | null = null;
  for (const [folderItemId, el] of folderEls.value.entries()) {
    if (folderItemId === item.id) continue;
    const r = el.getBoundingClientRect();
    const within = centerX >= r.left && centerX <= r.right && centerY >= r.top && centerY <= r.bottom;
    if (within) {
      const folder = itemsRef.value.find((i) => i.id === folderItemId);
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
    // Optimistic: remove from current list
    setItems(itemsRef.value.filter((i) => i.id !== item.id));
  } else if (currentFolder) {
    // inside folder: nothing else, position already updated
  } else {
    // nothing
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
    emit('update:items', itemsRef.value);
  } catch (error) {
    console.error('[Canvas] Error updating item position:', error);
  }
};

// Quick create functions
const createQuickTodo = async () => {
  try {
    const params = new URLSearchParams(window.location.search);
    const folderUuid = params.get('f');
    const response = await axios.post('/api/items', {
      type: 'todo',
      board_uuid: props.uuid,
      folder_uuid: folderUuid || undefined,
    });
    setItems([...itemsRef.value, response.data]);
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
    setItems([...itemsRef.value, response.data]);
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
    setItems([...itemsRef.value, response.data]);
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
    setItems([...itemsRef.value, response.data]);
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
    setItems([...itemsRef.value, response.data]);
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
    setItems([...itemsRef.value, response.data]);
  } catch (error) {
    console.error('Error creating event:', error);
  }
};

const deleteItem = async (id: number) => {
  if (!confirm('Are you sure you want to delete this item?')) return;
  try {
    await axios.delete(`/api/items/${id}`);
    setItems(itemsRef.value.filter((item) => item.id !== id));
  } catch (error) {
    console.error('Error deleting item:', error);
  }
};

const updateItem = async (itemId: number, data: any) => {
  try {
    const response = await axios.put(`/api/items/${itemId}`, data);
    const idx = itemsRef.value.findIndex((i) => i.id === itemId);
    if (idx !== -1) {
      const updated = { ...itemsRef.value[idx], ...response.data } as Item;
      const next = [...itemsRef.value];
      next[idx] = updated;
      setItems(next);
    }
  } catch (error) {
    console.error('[Canvas] Error updating item:', error);
  }
};

const openFolder = (folderUuid: string) => {
  window.location.assign(`/board/${props.uuid}?f=${encodeURIComponent(folderUuid)}`);
};

let prevBodyOverflow: string | null = null;

onMounted(() => {
  // Lock body scroll to avoid vertical scrollbar interfering with panning
  prevBodyOverflow = document.body.style.overflow || '';
  document.body.style.overflow = 'hidden';
});

onBeforeUnmount(() => {
  endPan();
  if (prevBodyOverflow !== null) {
    document.body.style.overflow = prevBodyOverflow;
  }
});
</script>

<template>
  <!-- Dropzone upload area -->
  <div
    class="rounded-box border border-dashed border-base-300 bg-base-200/40 p-4 text-center transition-colors"
    :class="{ 'bg-primary/10 border-primary': isDragging, 'opacity-60': isUploading }"
    @dragover="onDragOver"
    @dragleave="onDragLeave"
    @drop="onDrop"
  >
    <div class="flex flex-col items-center gap-2">
      <div class="text-sm">
        <span v-if="!isUploading">Dépose un fichier ici ou</span>
        <span v-else>Téléversement en cours… {{ uploadProgress }}%</span>
      </div>
      <button class="btn btn-sm" type="button" @click="onSelectClick" :disabled="isUploading">
        Choisir un fichier
      </button>
      <input ref="fileInputRef" type="file" class="hidden" @change="onFileSelected" />
      <div v-if="uploadError" class="text-error text-sm">{{ uploadError }}</div>
    </div>
  </div>

  <div class="relative h-full w-full flex-1">
    <div v-if="itemsRef.length === 0" class="p-4 text-center">No items found. Create some items to see them here!</div>

    <div
      ref="boardContainer"
      class="relative select-none"
      :style="{ width: BOARD_WIDTH + 'px', height: BOARD_HEIGHT + 'px', transform: `translate(${panX}px, ${panY}px)` }"
    >
      <!-- Transparent surface to initiate panning when clicking empty board space -->
      <div class="pan-surface absolute inset-0 z-0 cursor-grab" @mousedown="startPan" :class="{ 'cursor-grabbing': isPanning }"></div>

      <DraggableResizable
        v-for="item in itemsRef"
        :key="item.id"
        v-model:x="item.x"
        v-model:y="item.y"
        v-model:w="item.width"
        :active="true"
        :draggable="true"
        :resizable="false"
        :handles="['tl', 'tm', 'tr', 'mr', 'br', 'bm', 'bl', 'ml']"
        :min-width="160"
        @dragstop="onDragStop(item)"
        @resizestop="onDragStop(item)"
        class="absolute !border-none z-10"
      >
        <Item v-if="item.type === 'todo'">
          <Todo
            :id="item.id"
            :title="item.title"
            :completed="item.completed"
            :x="item.x"
            :y="item.y"
            :width="item.width"
            :height="item.height"
            @update="updateItem(item.id, $event)"
            @delete="deleteItem"
          />
        </Item>
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
          :content="(item as any).content"
          :color="item.color"
          :pinned="(item as any).pinned"
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
          :url="(item as any).url"
          :favicon_url="(item as any).favicon_url"
          :tags="(item as any).tags || []"
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
          :start_at="(item as any).start_at"
          :end_at="(item as any).end_at"
          :location="(item as any).location"
          :all_day="(item as any).all_day"
          :remind_minutes_before="(item as any).remind_minutes_before"
          :x="item.x"
          :y="item.y"
          :width="item.width"
          :height="item.height"
          @update="updateItem(item.id, $event)"
          @delete="deleteItem"
        />
        <DocumentItem
          v-else-if="item.type === 'document'"
          :id="item.id"
          :title="item.title"
          :description="item.description"
          :url="(item as any).url"
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
</template>
