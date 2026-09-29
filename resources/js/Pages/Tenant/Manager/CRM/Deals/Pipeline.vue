<template>
  <ManagerLayout :title="$t('crm.deal_pipeline')">
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('crm.deal_pipeline') }}</h1>
        <div class="flex gap-2">
          <button
            @click="showAddDeal = true"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
          >
            <i class="fa-solid fa-plus"></i> {{ $t('crm.new_deal') }}
          </button>
        </div>
      </div>

      <!-- Pipeline board -->
      <div class="flex gap-4 overflow-x-auto pb-4">
        <div
          v-for="stage in stages"
          :key="stage.id"
          class="flex-shrink-0 w-72 bg-gray-50 rounded-xl border border-gray-200"
        >
          <!-- Stage header -->
          <div class="flex items-center justify-between p-3 border-b border-gray-200">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full" :style="{ background: stage.color ?? '#6366f1' }"></span>
              <h3 class="text-sm font-semibold text-gray-700">{{ stage.name }}</h3>
              <span class="text-xs bg-white text-gray-500 rounded-full px-2 py-0.5 border border-gray-200">
                {{ stage.deals?.length ?? 0 }}
              </span>
            </div>
            <span class="text-xs text-gray-400 font-medium">
              {{ stageValue(stage) }}
            </span>
          </div>

          <!-- Deals -->
          <div class="p-2 space-y-2 min-h-[200px]">
            <div
              v-for="deal in stage.deals"
              :key="deal.id"
              @click="router.visit(route('tenant.manager.deals.show', deal.id))"
              class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm cursor-pointer hover:border-indigo-300 hover:shadow-md transition-all"
            >
              <p class="text-sm font-medium text-gray-900 mb-1">{{ deal.title }}</p>
              <p v-if="deal.client" class="text-xs text-gray-500 flex items-center gap-1 mb-2">
                <i class="fa-solid fa-building"></i>
                {{ deal.client.company_name || deal.client.name }}
              </p>
              <div class="flex items-center justify-between">
                <span v-if="deal.value" class="text-sm font-semibold text-indigo-600">
                  {{ Number(deal.value).toLocaleString(intlLocale()) }} {{ deal.currency ?? 'PLN' }}
                </span>
                <div
                  v-if="deal.assignee"
                  class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold"
                  :title="deal.assignee.name"
                >
                  {{ deal.assignee.name?.charAt(0)?.toUpperCase() }}
                </div>
              </div>
              <div v-if="deal.probability != null" class="mt-2">
                <div class="h-1 bg-gray-100 rounded-full">
                  <div class="h-1 bg-indigo-500 rounded-full" :style="{ width: deal.probability + '%' }"></div>
                </div>
                <p class="text-xs text-gray-400 mt-0.5 text-right">{{ deal.probability }}%</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  stages: Array,
  filters: Object,
})

const showAddDeal = ref(false)

const stageValue = (stage) => {
  const total = (stage.deals ?? []).reduce((s, d) => s + Number(d.value ?? 0), 0)
  if (!total) return ''
  return total.toLocaleString(intlLocale()) + ' PLN'
}
</script>
