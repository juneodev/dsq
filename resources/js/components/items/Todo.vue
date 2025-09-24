<script setup lang="ts">
import { Check, Trash, RotateCcw } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';

interface TodoProps {
    id: number;
    title: string;
    description?: string;
    completed: boolean;
    x: number;
    y: number;
    width: number;
    height: number;
}

const props = defineProps<TodoProps>();

const emit = defineEmits<{
    update: [data: Partial<TodoProps>];
    delete: [id: number];
}>();

const statusClass = computed(() =>
    props.completed
        ? 'bg-green-100 text-green-800'
        : 'bg-yellow-100 text-yellow-800',
);

// Inline edit state
const editing = ref(false);
const formTitle = ref(props.title);
const formDescription = ref(props.description ?? '');

const startEdit = async () => {
    formTitle.value = props.title;
    formDescription.value = props.description ?? '';
    editing.value = true;
    await nextTick();
};

const saveEdit = () => {
    emit('update', {
        title: formTitle.value,
        description: formDescription.value,
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
                <span
                    class="rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800"
                >
                    TODO
                </span>
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
        </div>

        <!-- Body -->
        <template v-if="editing">
            <textarea
                v-model="formDescription"
                class="mb-2 w-full flex-1 resize-none rounded border border-gray-300 p-2 text-sm text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                rows="4"
                placeholder="Description"
            />
        </template>
        <p v-else-if="description" class="mb-2 flex-1 text-sm text-gray-600">
            {{ description }}
        </p>

        <!-- Footer/actions -->
        <div class="mt-auto flex items-center justify-between">
            <template v-if="!editing">
                <span
                    :class="[
                        'rounded px-2 py-1 text-xs font-medium',
                        statusClass,
                    ]"
                >
                    {{ completed ? 'Completed' : 'Pending' }}
                </span>
                <div class="flex gap-1">
                    <button
                        @click="$emit('update', { completed: !completed })"
                        class="btn btn-square btn-soft btn-sm"
                        :class="completed ? 'btn-warning' : 'btn-success'"
                        :title="completed ? 'Mark as todo' : 'Mark as completed'"
                    >
                        <component :is="completed ? RotateCcw : Check" class="size-5 text-sm" />
                    </button>
                    <button
                        @click="$emit('delete', id)"
                        class="btn btn-square btn-soft btn-sm btn-error"
                        title="Delete todo"
                    >
                        <Trash class="size-5" />
                    </button>
                </div>
            </template>
            <template v-else>
                <div class="ml-auto flex gap-2">
                    <button
                        @click="saveEdit"
                        class="rounded bg-green-600 px-3 py-1 text-white hover:bg-green-700"
                        title="Save"
                    >
                        Save
                    </button>
                    <button
                        @click="cancelEdit"
                        class="rounded bg-gray-500 px-3 py-1 text-white hover:bg-gray-600"
                        title="Cancel"
                    >
                        Cancel
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>
