<template>
  <ManagerLayout :title="deal.title">
    <div class="space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.deals.pipeline')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <div>
            <h1 class="page-title">{{ deal.title }}</h1>
            <p class="page-subtitle">{{ deal.client?.company_name ?? deal.client?.name }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <Link :href="route('tenant.manager.deals.edit', deal.id)" class="btn-secondary">
            <i class="fa-solid fa-pen"></i> {{ $t('common.edit') }}
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar -->
        <div class="space-y-4">
          <div class="card p-5 space-y-4">
            <div>
              <label class="label text-xs">{{ $t('common.stage') }}</label>
              <select v-model="stageForm.stage_id" class="select" @change="moveStage">
                <option v-for="stage in stages" :key="stage.id" :value="stage.id">{{ stage.name }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <div class="text-gray-400 text-xs mb-0.5">{{ $t('common.value') }}</div>
                <div class="font-semibold text-gray-900">{{ formatMoney(deal.value) }}</div>
              </div>
              <div>
                <div class="text-gray-400 text-xs mb-0.5">{{ $t('crm.probability') }}</div>
                <div class="font-semibold text-gray-900">{{ deal.probability }}%</div>
              </div>
              <div>
                <div class="text-gray-400 text-xs mb-0.5">{{ $t('crm.close_date') }}</div>
                <div class="text-gray-700">
                  {{ deal.expected_close_date ? formatDate(deal.expected_close_date) : '—' }}
                </div>
              </div>
              <div>
                <div class="text-gray-400 text-xs mb-0.5">{{ $t('common.assigned') }}</div>
                <div class="text-gray-700">{{ deal.assigned?.name ?? '—' }}</div>
              </div>
            </div>
            <div>
              <div class="text-gray-400 text-xs mb-1">{{ $t('crm.progress_weighted_value') }}</div>
              <div class="progress-bar h-2">
                <div class="progress-fill h-2" :style="{ width: deal.probability + '%' }"></div>
              </div>
              <div class="text-xs text-gray-400 mt-1">
                {{ formatMoney((deal.value * deal.probability) / 100) }} {{ $t('crm.weighted_value') }}
              </div>
            </div>
          </div>

          <div v-if="deal.client" class="card p-5">
            <h3 class="section-title">{{ $t('common.client') }}</h3>
            <Link
              :href="route('tenant.manager.clients.show', deal.client.id)"
              class="text-sm font-medium text-indigo-600 hover:text-indigo-700"
            >
              {{ deal.client.company_name ?? deal.client.name }}
            </Link>
            <p class="text-xs text-gray-400 mt-1">{{ deal.client.email }}</p>
          </div>
        </div>

        <!-- Main -->
        <div class="lg:col-span-2 space-y-4">
          <div class="card p-5">
            <h3 class="section-title">{{ $t('common.description') }}</h3>
            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ deal.description || '—' }}</p>
          </div>

          <!-- Activities -->
          <div class="card p-5">
            <h3 class="section-title mb-3">{{ $t('crm.add_a_note_or_activity') }}</h3>
            <form @submit.prevent="addActivity" class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="label">{{ $t('common.type') }}</label>
                  <select v-model="activityForm.type" class="select">
                    <option value="note">{{ $t('common.note') }}</option>
                    <option value="call">{{ $t('common.phone') }}</option>
                    <option value="email">E-mail</option>
                    <option value="meeting">{{ $t('common.meeting') }}</option>
                  </select>
                </div>
                <div>
                  <label class="label">{{ $t('common.date') }}</label>
                  <input v-model="activityForm.date" type="date" class="input" />
                </div>
              </div>
              <textarea
                v-model="activityForm.description"
                class="textarea"
                rows="2"
                :placeholder="$t('crm.what_happened')"
                required
              ></textarea>
              <button type="submit" :disabled="activityForm.processing" class="btn-primary btn-sm">
                {{ $t('common.save') }}
              </button>
            </form>
          </div>

          <div class="card p-5">
            <h3 class="section-title">{{ $t('crm.history') }}</h3>
            <div v-if="deal.activities?.length" class="space-y-3 mt-2">
              <div v-for="act in deal.activities" :key="act.id" class="timeline-item">
                <div class="timeline-dot bg-indigo-100 text-indigo-600 text-xs w-7 h-7">
                  <i :class="activityIcon(act.type)"></i>
                </div>
                <div>
                  <p class="text-sm text-gray-700">{{ act.description }}</p>
                  <p class="text-xs text-gray-400">{{ formatDate(act.date) }} · {{ act.user?.name }}</p>
                </div>
              </div>
            </div>
            <p v-else class="text-sm text-gray-400">{{ $t('crm.no_history') }}</p>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  deal: Object,
  stages: { type: Array, default: () => [] },
})

const stageForm = reactive({ stage_id: props.deal.stage_id })

const moveStage = () => {
  useForm({ stage_id: stageForm.stage_id }).patch(route('tenant.manager.deals.move-stage', props.deal.id))
}

const activityForm = useForm({ type: 'note', date: new Date().toISOString().slice(0, 10), description: '' })

const addActivity = () => {
  activityForm.post(route('tenant.manager.deals.activity', props.deal.id), {
    onSuccess: () => activityForm.reset('description'),
  })
}

const activityIcon = (t) =>
  ({
    call: 'fa-solid fa-phone',
    email: 'fa-solid fa-envelope',
    meeting: 'fa-solid fa-calendar',
    note: 'fa-solid fa-note-sticky',
  })[t] ?? 'fa-solid fa-circle'
const formatMoney = (v) => new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
</script>
