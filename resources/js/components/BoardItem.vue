<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Board {
  id: number;
  uuid?: string;
  title: string;
  description?: string | null;
  created_at: string;
  updated_at: string;
}

defineProps<{
  board: Board;
  isEditing: boolean;
  editTitle: string;
  editDescription: string;
}>();

const emit = defineEmits<{
  (e: 'update:editTitle', value: string): void;
  (e: 'update:editDescription', value: string): void;
  (e: 'start-edit', board: Board): void;
  (e: 'cancel-edit'): void;
  (e: 'save', id: number): void;
  (e: 'delete', id: number): void;
}>();
</script>

<template>
  <div class="flex flex-col gap-2">
    <template v-if="isEditing">
      <input
        :value="editTitle"
        @input="emit('update:editTitle', ($event.target as HTMLInputElement).value)"
        type="text"
        class="input input-bordered"
        placeholder="Title"
      />
      <input
        :value="editDescription"
        @input="emit('update:editDescription', ($event.target as HTMLInputElement).value)"
        type="text"
        class="input input-bordered"
        placeholder="Description (optional)"
      />
      <div class="mt-2 flex gap-2">
        <button class="btn btn-success btn-sm" @click="emit('save', board.id)">Save</button>
        <button class="btn btn-ghost btn-sm" @click="emit('cancel-edit')">Cancel</button>
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
          <button class="btn btn-sm" @click="emit('start-edit', board)">Edit</button>
          <button class="btn btn-sm btn-error" @click="emit('delete', board.id)">Delete</button>
        </div>
      </div>
    </template>
  </div>
</template>
