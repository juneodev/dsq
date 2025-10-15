<script setup lang="ts">
import { Trash, ExternalLink, Pencil, Save, X } from 'lucide-vue-next';
import { ref, nextTick } from 'vue';

interface DocumentProps {
  id: number;
  title: string;
  description?: string;
  url?: string | null;
  x: number;
  y: number;
  width: number;
  height: number;
}

const props = defineProps<DocumentProps>();

const emit = defineEmits<{
  update: [data: Partial<DocumentProps>];
  delete: [id: number];
}>();

// Inline edit state (similar to Todo)
const editing = ref(false);
const formTitle = ref(props.title);
const formDescription = ref(props.description ?? '');
const formUrl = ref(props.url ?? '');

const startEdit = async () => {
  formTitle.value = props.title;
  formDescription.value = props.description ?? '';
  formUrl.value = props.url ?? '';
  editing.value = true;
  await nextTick();
};

const saveEdit = () => {
  emit('update', {
    title: formTitle.value,
    description: formDescription.value,
    url: formUrl.value || null,
  });
  editing.value = false;
};

const cancelEdit = () => {
  editing.value = false;
};
</script>

<template>
  <div
    class="flex h-full flex-col rounded-box bg-base-100 p-4 shadow-md cursor-pointer"
    @dblclick="startEdit"
  >
    <!-- Header -->
    <div class="mb-2 justify-between">
      <div class="flex items-center gap-2">
        <span class="rounded bg-purple-100 px-2 py-1 text-xs font-medium text-purple-800">
          DOCUMENT
        </span>
        <h3 v-if="!editing" class="text-lg font-semibold text-gray-800">
          {{ title }}
        </h3>
        <input
          v-else
          v-model="formTitle"
          type="text"
          class="w-full rounded border border-gray-300 px-2 py-1 text-lg font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none"
          placeholder="Title"
        />
      </div>
    </div>

    <!-- Body -->
    <template v-if="editing">
      <input
        v-model="formUrl"
        type="text"
        class="mb-2 w-full rounded border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
        placeholder="https://… (optional)"
      />
      <textarea
        v-model="formDescription"
        class="mb-2 w-full flex-1 resize-none rounded border border-gray-300 p-2 text-sm text-gray-700 focus:ring-2 focus:ring-purple-500 focus:outline-none"
        rows="3"
        placeholder="Description (optional)"
      />
    </template>

    <div v-else class="flex-1">
      <p v-if="description" class="mb-2 text-sm text-gray-600">{{ description }}</p>
      <a
        v-if="url"
        :href="url"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center gap-1 text-sm text-primary hover:underline"
        :title="'Open ' + title"
      >
        <ExternalLink class="size-4" />
        Ouvrir le document
      </a>
      <p v-else class="text-xs text-gray-400 italic">Aucun fichier ou URL associé</p>
    </div>

    <!-- Footer/actions -->
    <div class="mt-auto flex items-center justify-end gap-2">
      <button v-if="!editing" @click="startEdit" class="btn btn-soft btn-sm" title="Edit">
        <Pencil class="size-4" />
      </button>
      <button v-if="!editing" @click="$emit('delete', id)" class="btn btn-soft btn-sm btn-error" title="Delete">
        <Trash class="size-4" />
      </button>
      <template v-else>
        <button @click="saveEdit" class="btn btn-success btn-sm" title="Save">
          <Save class="size-4" />
        </button>
        <button @click="cancelEdit" class="btn btn-ghost btn-sm" title="Cancel">
          <X class="size-4" />
        </button>
      </template>
    </div>
  </div>
</template>
