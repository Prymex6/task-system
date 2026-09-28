<template>
  <Link
    :href="href"
    :class="[
      active ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white',
      'group flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
    ]"
  >
    <i :class="[icon, 'w-5 text-center text-sm flex-shrink-0']"></i>
    <span v-if="label" class="flex-1 whitespace-nowrap overflow-hidden">{{ label }}</span>
    <span
      v-if="badge && badge > 0"
      class="ml-auto bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.1rem] h-5 flex items-center justify-center px-1"
    >
      {{ badge > 99 ? '99+' : badge }}
    </span>
  </Link>
</template>

<script setup>
defineProps({
  href: { type: String, required: true },
  icon: { type: String, required: true },
  label: { type: String, default: '' },
  active: { type: Boolean, default: false },
  badge: { type: Number, default: 0 },
})
</script>
