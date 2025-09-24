<script setup lang="ts">
import { cn } from '@/lib/utils';
import {
    DropdownMenuContent,
    type DropdownMenuContentEmits,
    type DropdownMenuContentProps,
    DropdownMenuPortal,
    useForwardPropsEmits
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

const props = withDefaults(
    defineProps<DropdownMenuContentProps & { class?: HTMLAttributes['class'] }>(),
    {
        sideOffset: 4
    }
);
const emits = defineEmits<DropdownMenuContentEmits>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <DropdownMenuPortal>
        <DropdownMenuContent
            data-slot="dropdown-menu-content"
            v-bind="forwarded"
            :class="cn('menu bg-base-100 border-2 border-dashed border-base-content/30 rounded-box w-56', props.class)"
        >
            <slot />
        </DropdownMenuContent>
    </DropdownMenuPortal>
</template>
