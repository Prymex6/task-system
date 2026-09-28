<template>
  <div v-if="links && links.length > 3" class="flex items-center justify-center gap-1 mt-4">
    <template v-for="link in links" :key="link.label">
      <Link
        v-if="link.url"
        :href="link.url"
        :class="[
          link.active
            ? 'bg-indigo-600 text-white border-indigo-600'
            : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50',
          'inline-flex items-center justify-center w-9 h-9 text-sm border rounded-lg transition-colors',
        ]"
        v-html="link.label"
        preserve-scroll
      />
      <span v-else class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-400" v-html="link.label" />
    </template>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  links: Array,
})
</script>
