<script setup lang="ts">
import { ref, nextTick } from 'vue';
import { Trash, Pin, PinOff } from 'lucide-vue-next';

interface NoteProps {
    id: number;
    title: string;
    content?: string;
    color?: string;
    pinned?: boolean;
    x: number;
    y: number;
    width: number;
    height: number;
}

const props = defineProps<NoteProps>();

const emit = defineEmits<{
    update: [data: Partial<NoteProps>];
    delete: [id: number];
}>();

const editing = ref(false);
const formTitle = ref(props.title ?? '');
const formContent = ref(props.content ?? '');
const formColor = ref(props.color ?? '#FEF3C7');

const startEdit = async () => {
    formTitle.value = props.title ?? '';
    formContent.value = props.content ?? '';
    formColor.value = props.color ?? '#FEF3C7';
    editing.value = true;
    await nextTick();
};

const saveEdit = () => {
    emit('update', {
        title: formTitle.value,
        content: formContent.value,
        color: formColor.value,
    });
    editing.value = false;
};

const cancelEdit = () => {
    editing.value = false;
};
</script>

<template>
    <div
        class="flex h-full flex-col rounded-box p-4 shadow-md cursor-pointer"
        :style="{ backgroundColor: color || '#FEF3C7' }"
        @dblclick="startEdit"
    >
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="rounded bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">NOTE</span>
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
            <button
                class="btn btn-square btn-soft btn-sm"
                :class="pinned ? 'btn-warning' : 'btn-ghost'"
                @click="$emit('update', { pinned: !pinned })"
                :title="pinned ? 'Unpin' : 'Pin'"
            >
                <component :is="pinned ? PinOff : Pin" class="size-5" />
            </button>
        </div>

        <template v-if="editing">
            <textarea
                v-model="formContent"
                class="mb-2 w-full flex-1 resize-none rounded border border-gray-300 p-2 text-sm text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                rows="6"
                placeholder="Take a note..."
            />
            <div class="flex items-center gap-2 mt-2">
                <label class="text-xs text-gray-700">Color</label>
                <input type="color" v-model="formColor" class="w-8 h-6 border border-gray-300 rounded" />
            </div>
        </template>
        <p v-else-if="content" class="mb-2 flex-1 whitespace-pre-wrap text-sm text-gray-800">
            {{ content }}
        </p>

        <div class="mt-auto flex items-center justify-end gap-2">
            <template v-if="editing">
                <button @click="saveEdit" class="rounded bg-green-600 px-3 py-1 text-white hover:bg-green-700">Save</button>
                <button @click="cancelEdit" class="rounded bg-gray-500 px-3 py-1 text-white hover:bg-gray-600">Cancel</button>
            </template>
            <button @click="$emit('delete', id)" class="btn btn-square btn-soft btn-sm btn-error" title="Delete note">
                <Trash class="size-5" />
            </button>
        </div>
    </div>
</template>
