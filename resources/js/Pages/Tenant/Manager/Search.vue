<template>
  <ManagerLayout :title="$t('manager.search_2')">
    <div class="max-w-3xl space-y-5">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('manager.search_2') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('manager.projects_tasks_clients_invoices_tickets_and') }}</p>
      </div>

      <form @submit.prevent="submit" class="flex gap-2">
        <input
          v-model="term"
          type="search"
          class="input flex-1"
          :placeholder="$t('manager.type_at_least_two_characters')"
          autofocus
        />
        <button type="submit" class="btn-primary">{{ $t('manager.search') }}</button>
      </form>

      <div v-if="query && !results.length" class="py-16 text-center bg-white rounded-xl border border-gray-200">
        <i class="fa-solid fa-magnifying-glass text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">{{ $t('manager.nothing_found_for') }}{{ query }}”</p>
      </div>

      <div v-else-if="results.length" class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
        <Link
          v-for="result in results"
          :key="`${result.type}-${result.id}`"
          :href="result.url"
          class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50"
        >
          <i :class="iconFor(result.type)" class="w-5 text-center text-gray-400"></i>
          <span class="flex-1">
            <span class="block text-sm font-medium text-gray-900">{{ result.title }}</span>
            <span class="block text-xs text-gray-500">{{ result.subtitle }}</span>
          </span>
          <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
        </Link>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  query: { type: String, default: '' },
  results: { type: Array, default: () => [] },
})

const term = ref(props.query)

const icons = {
  project: 'fa-solid fa-diagram-project',
  task: 'fa-solid fa-circle-check',
  client: 'fa-solid fa-building',
  invoice: 'fa-solid fa-file-invoice',
  ticket: 'fa-solid fa-life-ring',
  kb: 'fa-solid fa-book',
}

const iconFor = (type) => icons[type] ?? 'fa-solid fa-magnifying-glass'

const submit = () => router.get(route('tenant.manager.search'), { q: term.value }, { preserveState: true })
</script>
