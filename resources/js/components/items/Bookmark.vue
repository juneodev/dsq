<script setup lang="ts">
import { ref, nextTick, computed } from 'vue';
import { Trash, ExternalLink } from 'lucide-vue-next';

interface BookmarkProps {
    id: number;
    title: string;
    url: string;
    favicon_url?: string | null;
    tags?: string[];
    x: number;
    y: number;
    width: number;
    height: number;
}

const props = defineProps<BookmarkProps>();

const emit = defineEmits<{
    update: [data: Partial<BookmarkProps>];
    delete: [id: number];
}>();

const editing = ref(false);
const formTitle = ref(props.title ?? '');
const formUrl = ref(props.url ?? '');
const formFavicon = ref(props.favicon_url ?? '');
const formTags = ref<string[]>(props.tags ?? []);
const newTag = ref('');

const hostname = computed(() => {
    try {
        return new URL(props.url).hostname;
    } catch {
        return props.url;
    }
});

const startEdit = async () => {
    formTitle.value = props.title ?? '';
    formUrl.value = props.url ?? '';
    formFavicon.value = props.favicon_url ?? '';
    formTags.value = [...(props.tags ?? [])];
    editing.value = true;
    await nextTick();
};

const saveEdit = () => {
    emit('update', {
        title: formTitle.value,
        url: formUrl.value,
        favicon_url: formFavicon.value || null,
        tags: formTags.value,
    });
    editing.value = false;
};

const cancelEdit = () => {
    editing.value = false;
};

const addTag = () => {
    const t = newTag.value.trim();
    if (!t) return;
    if (!formTags.value.includes(t)) formTags.value.push(t);
    newTag.value = '';
};

const removeTag = (t: string) => {
    formTags.value = formTags.value.filter(x => x !== t);
};
</script>

<template>
    <div class="flex h-full flex-col rounded-box bg-base-100 p-4 shadow-md cursor-pointer" @dblclick="startEdit">
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="rounded bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-800">BOOKMARK</span>
                <h3 v-if="!editing" class="text-lg font-semibold text-gray-800">
                    {{ title }}
                </h3>
                <input
                    v-else
                    v-model="formTitle"
                    type="text"
                    class="w-full rounded border border-gray-300 px-2 py-1 text-lg font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    placeholder="Title"
                />
            </div>
            <a :href="url" target="_blank" rel="noopener" class="btn btn-square btn-soft btn-sm" title="Open link">
                <ExternalLink class="size-5" />
            </a>
        </div>

        <template v-if="editing">
            <div class="space-y-2">
                <input v-model="formUrl" type="text" class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="https://..." />
                <input v-model="formFavicon" type="text" class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Favicon URL (optional)" />
                <div class="flex items-center gap-2">
                    <input v-model="newTag" @keyup.enter="addTag" type="text" class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Add tag" />
                    <button @click="addTag" class="px-2 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700">Add</button>
                </div>
                <div class="flex flex-wrap gap-1">
                    <span v-for="t in formTags" :key="t" class="inline-flex items-center gap-1 bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded">
                        {{ t }}
                        <button @click="removeTag(t)" class="text-gray-600 hover:text-gray-900">×</button>
                    </span>
                </div>
            </div>
        </template>
        <div v-else class="flex items-center gap-3">
            <img v-if="favicon_url" :src="favicon_url" alt="favicon" class="h-6 w-6 rounded" />
            <div class="min-w-0">
                <a :href="url" target="_blank" rel="noopener" class="block truncate text-blue-600 hover:underline text-sm">{{ url }}</a>
                <span class="text-xs text-gray-600">{{ hostname }}</span>
            </div>
        </div>

        <div class="mt-auto flex items-center justify-end gap-2">
            <template v-if="editing">
                <button @click="saveEdit" class="rounded bg-green-600 px-3 py-1 text-white hover:bg-green-700">Save</button>
                <button @click="cancelEdit" class="rounded bg-gray-500 px-3 py-1 text-white hover:bg-gray-600">Cancel</button>
            </template>
            <button @click="$emit('delete', id)" class="btn btn-square btn-soft btn-sm btn-error" title="Delete bookmark">
                <Trash class="size-5" />
            </button>
        </div>
    </div>
</template>
