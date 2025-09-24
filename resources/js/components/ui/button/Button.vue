<script setup lang="ts">
import type { HTMLAttributes } from 'vue'

// DaisyUI v5-based simple button
// Props map directly to DaisyUI utility classes
interface Props {
  // HTML tag to render
  as?: string
  // Color palette (daisyUI)
  color?:
    | 'primary'
    | 'secondary'
    | 'accent'
    | 'neutral'
    | 'info'
    | 'success'
    | 'warning'
    | 'error'
  // Visual variant/tone
  variant?: 'solid' | 'outline' | 'soft' | 'ghost' | 'link'
  // Size
  size?: 'xs' | 'sm' | 'md' | 'lg'
  // Shape helpers
  square?: boolean
  circle?: boolean
  // Layout helpers
  wide?: boolean
  block?: boolean
  // Loading state
  loading?: boolean
  // Extra classes
  class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
  as: 'button',
  variant: 'solid',
})
</script>

<template>
  <component
    :is="props.as"
    data-slot="button"
    :class="[
      'btn',
      // color (works with all variants; daisyUI combines tone + color)
      props.color ? `btn-${props.color}` : undefined,
      // variant tone
      props.variant === 'outline' ? 'btn-outline' : undefined,
      props.variant === 'soft' ? 'btn-soft' : undefined,
      props.variant === 'ghost' ? 'btn-ghost' : undefined,
      props.variant === 'link' ? 'btn-link' : undefined,
      // size
      props.size ? `btn-${props.size}` : undefined,
      // shape
      props.square ? 'btn-square' : undefined,
      props.circle ? 'btn-circle' : undefined,
      // layout
      props.wide ? 'btn-wide' : undefined,
      props.block ? 'btn-block' : undefined,
      // state
      props.loading ? 'loading' : undefined,
      props.class,
    ]"
    v-bind="$attrs"
  >
    <slot />
  </component>
</template>
