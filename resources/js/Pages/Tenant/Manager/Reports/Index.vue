<template>
  <ManagerLayout :title="$t('reports.title')">
    <div class="max-w-4xl space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('reports.title') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('reports.subtitle') }}</p>
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <Link
          v-for="card in cards"
          :key="card.route"
          :href="route(card.route)"
          class="bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-sm transition"
        >
          <div class="flex items-start gap-3">
            <span class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
              <i :class="card.icon"></i>
            </span>
            <span>
              <span class="block font-medium text-gray-900">{{ card.title }}</span>
              <span class="block text-sm text-gray-500 mt-0.5">{{ card.description }}</span>
            </span>
          </div>
        </Link>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const { t } = useI18n()

const cards = computed(() => [
  {
    route: 'tenant.manager.reports.time',
    icon: 'fa-solid fa-clock',
    title: t('reports.time_title'),
    description: t('reports.time_description'),
  },
  {
    route: 'tenant.manager.reports.finance',
    icon: 'fa-solid fa-chart-line',
    title: t('reports.finance_title'),
    description: t('reports.finance_description'),
  },
  {
    route: 'tenant.manager.reports.projects',
    icon: 'fa-solid fa-diagram-project',
    title: t('reports.projects_title'),
    description: t('reports.projects_description'),
  },
  {
    route: 'tenant.manager.reports.staff',
    icon: 'fa-solid fa-users',
    title: t('reports.staff_title'),
    description: t('reports.staff_description'),
  },
  {
    route: 'tenant.manager.reports.clients',
    icon: 'fa-solid fa-address-book',
    title: t('reports.clients_title'),
    description: t('reports.clients_description'),
  },
])
</script>
