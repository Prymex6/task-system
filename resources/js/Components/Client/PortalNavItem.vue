<template>
  <Link
    :href="href"
    :class="[
      active ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
      'flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors',
    ]"
  >
    <i :class="[icon, 'w-5 text-center text-sm flex-shrink-0', active ? 'text-indigo-600' : 'text-gray-400']"></i>
    <span class="flex-1">{{ label }}</span>
    <span
      v-if="badge && badge > 0"
      class="bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.1rem] h-5 flex items-center justify-center px-1"
    >
      {{ badge > 99 ? '99+' : badge }}
    </span>
  </Link>
</template>

<script setup>
defineProps({
  href: { type: String, required: true },
  icon: { type: String, required: true },
  label: { type: String, required: true },
  active: { type: Boolean, default: false },
  badge: { type: Number, default: 0 },
})
</script>
