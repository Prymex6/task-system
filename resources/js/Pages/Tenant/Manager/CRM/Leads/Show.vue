<template>
  <ManagerLayout :title="lead.name">
    <div class="space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.leads.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <div>
            <h1 class="page-title">{{ lead.name }}</h1>
            <p class="page-subtitle">{{ lead.company }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button v-if="lead.status !== 'won' && lead.status !== 'lost'" @click="convertLead" class="btn-success">
            <i class="fa-solid fa-user-check"></i> {{ $t('crm.convert_to_client') }}
          </button>
          <Link :href="route('tenant.manager.leads.edit', lead.id)" class="btn-secondary">
            <i class="fa-solid fa-pen"></i> {{ $t('common.edit') }}
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Details -->
        <div class="space-y-4">
          <div class="card p-5 space-y-3">
            <div class="flex items-center justify-between mb-2">
              <h3 class="section-title mb-0">{{ $t('common.status') }}</h3>
              <select v-model="statusForm.status" class="select input-sm w-36" @change="updateStatus">
                <option value="new">{{ $t('common.new') }}</option>
                <option value="contacted">{{ $t('common.contacted') }}</option>
                <option value="qualified">{{ $t('crm.qualified') }}</option>
                <option value="proposal">{{ $t('common.proposal') }}</option>
                <option value="won">{{ $t('common.won') }}</option>
                <option value="lost">{{ $t('common.lost') }}</option>
              </select>
            </div>
            <div class="text-sm space-y-2">
              <div v-if="lead.email" class="flex gap-2">
                <i class="fa-solid fa-envelope w-4 text-gray-400"></i
                ><a :href="`mailto:${lead.email}`" class="text-gray-700 hover:text-indigo-600">{{ lead.email }}</a>
              </div>
              <div v-if="lead.phone" class="flex gap-2">
                <i class="fa-solid fa-phone w-4 text-gray-400"></i><span class="text-gray-700">{{ lead.phone }}</span>
              </div>
              <div v-if="lead.source" class="flex gap-2">
                <i class="fa-solid fa-tag w-4 text-gray-400"></i><span class="text-gray-600">{{ lead.source }}</span>
              </div>
              <div v-if="lead.value" class="flex gap-2">
                <i class="fa-solid fa-coins w-4 text-gray-400"></i
                ><span class="text-gray-700 font-medium">{{ formatMoney(lead.value) }}</span>
              </div>
              <div v-if="lead.assigned" class="flex gap-2">
                <i class="fa-solid fa-user w-4 text-gray-400"></i
                ><span class="text-gray-600">{{ lead.assigned.name }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Activities & Notes -->
        <div class="lg:col-span-2 space-y-4">
          <div class="card p-5">
            <h3 class="section-title">{{ $t('common.description') }}</h3>
            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ lead.description || '—' }}</p>
          </div>

          <!-- Add Activity -->
          <div class="card p-5">
            <h3 class="section-title mb-3">{{ $t('crm.add_activity') }}</h3>
            <form @submit.prevent="addActivity" class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="label">{{ $t('common.type') }}</label>
                  <select v-model="activityForm.type" class="select">
                    <option value="call">{{ $t('common.phone') }}</option>
                    <option value="email">E-mail</option>
                    <option value="meeting">{{ $t('common.meeting') }}</option>
                    <option value="note">{{ $t('common.note') }}</option>
                  </select>
                </div>
                <div>
                  <label class="label">{{ $t('common.date') }}</label>
                  <input v-model="activityForm.date" type="date" class="input" />
                </div>
              </div>
              <div>
                <label class="label">{{ $t('common.description') }}</label>
                <textarea v-model="activityForm.description" class="textarea" rows="2" required></textarea>
              </div>
              <button type="submit" :disabled="activityForm.processing" class="btn-primary btn-sm">
                {{ $t('common.add') }}
              </button>
            </form>
          </div>

          <!-- Activity timeline -->
          <div class="card p-5">
            <h3 class="section-title">{{ $t('crm.activity_history') }}</h3>
            <div v-if="lead.activities?.length" class="space-y-3 mt-2">
              <div v-for="act in lead.activities" :key="act.id" class="timeline-item">
                <div class="timeline-dot bg-indigo-100 text-indigo-600 text-xs">
                  <i :class="activityIcon(act.type)"></i>
                </div>
                <div>
                  <p class="text-sm text-gray-700">{{ act.description }}</p>
                  <p class="text-xs text-gray-400">{{ formatDate(act.date) }} · {{ act.user?.name }}</p>
                </div>
              </div>
            </div>
            <p v-else class="text-sm text-gray-400">{{ $t('crm.no_activity') }}</p>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { reactive } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({ lead: Object })

const statusForm = reactive({ status: props.lead.status })

const updateStatus = () => {
  useForm({ status: statusForm.status }).patch(route('tenant.manager.leads.update', props.lead.id))
}

const convertLead = () => {
  if (!confirm(t('crm.convert_this_lead_into_a_client'))) return
  useForm({}).post(route('tenant.manager.leads.convert', props.lead.id))
}

const activityForm = useForm({ type: 'note', date: new Date().toISOString().slice(0, 10), description: '' })

const addActivity = () => {
  activityForm.post(route('tenant.manager.leads.activity', props.lead.id), {
    onSuccess: () => activityForm.reset('description'),
  })
}

const activityIcon = (type) =>
  ({
    call: 'fa-solid fa-phone',
    email: 'fa-solid fa-envelope',
    meeting: 'fa-solid fa-calendar',
    note: 'fa-solid fa-sticky-note',
  })[type] ?? 'fa-solid fa-circle'

const formatMoney = (v) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
