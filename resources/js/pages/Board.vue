<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Canvas from '@/components/board/Canvas.vue';
import ListView from '@/components/board/List.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

const props = defineProps<{ uuid: string; breadcrumbs?: BreadcrumbItem[] }>();

const breadcrumbItems: BreadcrumbItem[] = props.breadcrumbs ?? [
  { title: 'Dashboard', href: dashboard().url },
];

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

const items = ref<Item[]>([]);
const loading = ref(true);
const viewMode = ref<'canvas' | 'list'>('canvas');

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

onMounted(fetchItems);
</script>

<template>
  <Head title="Board" />
  <AppLayout :breadcrumbs="breadcrumbItems">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
      <div class="flex items-center justify-between">
        <div class="join">
          <button class="btn btn-sm join-item" :class="{ 'btn-primary': viewMode === 'canvas' }" @click="viewMode = 'canvas'">Canvas</button>
          <button class="btn btn-sm join-item" :class="{ 'btn-primary': viewMode === 'list' }" @click="viewMode = 'list'">Liste</button>
        </div>
      </div>

      <div v-if="loading" class="p-4 text-center">Loading items...</div>
      <template v-else>
        <Canvas v-if="viewMode === 'canvas'" :uuid="props.uuid" v-model:items="items" />
        <ListView v-else :items="items" />
      </template>
    </div>
  </AppLayout>
</template>
