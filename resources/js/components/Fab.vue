<template>
  <div class="fab">
    <!-- a focusable div with tabindex is necessary to work on all browsers. role="button" is necessary for accessibility -->
    <div tabindex="0" role="button" class="btn btn-lg btn-circle btn-primary">
      <slot name="main">F</slot>
    </div>

    <!-- buttons that show up when FAB is open -->
    <button class="btn btn-lg btn-circle" @click="$emit('action-a')">
      <slot name="action-a">A</slot>
    </button>
    <button class="btn btn-lg btn-circle" @click="$emit('action-b')">
      <slot name="action-b">B</slot>
    </button>
    <button class="btn btn-lg btn-circle" @click="$emit('action-c')">
      <slot name="action-c">C</slot>
    </button>
  </div>
</template>

<script setup lang="ts">
// Minimal, presentational component for the Floating Action Button (FAB).
// Uses named slots to allow customization while preserving current defaults.

defineEmits<{
  (e: 'action-a'): void
  (e: 'action-b'): void
  (e: 'action-c'): void
}>()
</script>
