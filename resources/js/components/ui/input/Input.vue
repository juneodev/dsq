<script setup lang="ts">
import type { HTMLAttributes } from 'vue'

interface Props {
  modelValue?: string | number
  type?: string
  placeholder?: string
  // DaisyUI color states
  color?:
    | 'primary'
    | 'secondary'
    | 'accent'
    | 'neutral'
    | 'info'
    | 'success'
    | 'warning'
    | 'error'
  // DaisyUI input variants
  variant?: 'default' | 'bordered'
  // Size
  size?: 'xs' | 'sm' | 'md' | 'lg'
  // Extra classes
  class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  variant: 'default',
})

const emit = defineEmits<{ (e: 'update:modelValue', value: string | number): void }>()

const onInput = (e: Event) => {
  const target = e.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <input
    :value="modelValue as any"
    @input="onInput"
    :type="props.type"
    :placeholder="props.placeholder"
    data-slot="input"
    :class="[
      'input',
      props.variant === 'bordered' ? 'input-bordered' : undefined,
      props.color ? `input-${props.color}` : undefined,
      props.size ? `input-${props.size}` : undefined,
      props.class,
    ]"
    v-bind="$attrs"
  />
</template>
