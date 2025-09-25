<script setup lang="ts">
import { ref, nextTick, computed } from 'vue';
import { Trash, CalendarClock } from 'lucide-vue-next';

interface EventProps {
    id: number;
    title: string;
    start_at: string;
    end_at?: string | null;
    location?: string | null;
    all_day?: boolean;
    remind_minutes_before?: number | null;
    x: number;
    y: number;
    width: number;
    height: number;
}

const props = defineProps<EventProps>();

const emit = defineEmits<{
    update: [data: Partial<EventProps>];
    delete: [id: number];
}>();

const editing = ref(false);
const formTitle = ref(props.title ?? '');
const formStart = ref(props.start_at ? props.start_at.substring(0, 16) : '');
const formEnd = ref(props.end_at ? props.end_at.substring(0, 16) : '');
const formLocation = ref(props.location ?? '');
const formAllDay = ref(!!props.all_day);
const formRemind = ref<number | null>(props.remind_minutes_before ?? null);

const startDateLabel = computed(() => new Date(props.start_at).toLocaleString());
const endDateLabel = computed(() => (props.end_at ? new Date(props.end_at).toLocaleString() : null));

const startEdit = async () => {
    formTitle.value = props.title ?? '';
    formStart.value = props.start_at ? props.start_at.substring(0, 16) : '';
    formEnd.value = props.end_at ? props.end_at.substring(0, 16) : '';
    formLocation.value = props.location ?? '';
    formAllDay.value = !!props.all_day;
    formRemind.value = props.remind_minutes_before ?? null;
    editing.value = true;
    await nextTick();
};

const saveEdit = () => {
    emit('update', {
        title: formTitle.value,
        start_at: formAllDay.value && formStart.value ? new Date(formStart.value).toISOString() : (formStart.value ? new Date(formStart.value).toISOString() : undefined),
        end_at: formAllDay.value ? null : (formEnd.value ? new Date(formEnd.value).toISOString() : null),
        location: formLocation.value || null,
        all_day: formAllDay.value,
        remind_minutes_before: formRemind.value,
    });
    editing.value = false;
};

const cancelEdit = () => {
    editing.value = false;
};
</script>

<template>
    <div class="flex h-full flex-col rounded-box bg-base-100 p-4 shadow-md cursor-pointer" @dblclick="startEdit">
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="rounded bg-rose-100 px-2 py-1 text-xs font-medium text-rose-800">EVENT</span>
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
            <CalendarClock class="size-5 text-rose-600" />
        </div>

        <template v-if="editing">
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-700 w-24">All day</label>
                    <input type="checkbox" v-model="formAllDay" class="toggle toggle-sm" />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-700 w-24">Start</label>
                    <input type="datetime-local" v-model="formStart" class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                </div>
                <div class="flex items-center gap-2" v-if="!formAllDay">
                    <label class="text-xs text-gray-700 w-24">End</label>
                    <input type="datetime-local" v-model="formEnd" class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-700 w-24">Location</label>
                    <input type="text" v-model="formLocation" class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-700 w-24">Remind (min)</label>
                    <input type="number" min="0" v-model.number="formRemind" class="w-32 rounded border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                </div>
            </div>
        </template>
        <div v-else class="text-sm text-gray-700 space-y-1">
            <div>
                <span class="font-medium">When:</span>
                <span>{{ startDateLabel }}</span>
                <template v-if="endDateLabel && !all_day">
                    <span> → {{ endDateLabel }}</span>
                </template>
                <span v-if="all_day" class="ml-2 rounded bg-gray-200 px-2 py-0.5 text-xs">All day</span>
            </div>
            <div v-if="location">
                <span class="font-medium">Where:</span>
                <span>{{ location }}</span>
            </div>
            <div v-if="remind_minutes_before">
                <span class="font-medium">Reminder:</span>
                <span>{{ remind_minutes_before }} min before</span>
            </div>
        </div>

        <div class="mt-auto flex items-center justify-end gap-2">
            <template v-if="editing">
                <button @click="saveEdit" class="rounded bg-green-600 px-3 py-1 text-white hover:bg-green-700">Save</button>
                <button @click="cancelEdit" class="rounded bg-gray-500 px-3 py-1 text-white hover:bg-gray-600">Cancel</button>
            </template>
            <button @click="$emit('delete', id)" class="btn btn-square btn-soft btn-sm btn-error" title="Delete event">
                <Trash class="size-5" />
            </button>
        </div>
    </div>
</template>
