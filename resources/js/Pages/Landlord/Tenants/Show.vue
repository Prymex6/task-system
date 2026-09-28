<template>
  <LandlordLayout :title="tenant.name ?? tenant.id">
    <div class="max-w-3xl space-y-5">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link :href="route('landlord.tenants.index')" class="text-gray-400 hover:text-gray-600">
            <i class="fa-solid fa-arrow-left"></i>
          </Link>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ tenant.name ?? tenant.id }}</h1>
            <p class="text-sm text-gray-500">{{ tenant.domain }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span
            class="text-xs px-2 py-0.5 rounded-full font-medium"
            :class="tenant.is_suspended ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
          >
            {{ tenant.is_suspended ? 'Zawieszone' : 'Aktywne' }}
          </span>
          <button
            v-if="!tenant.is_suspended"
            @click="suspend"
            class="text-xs text-orange-600 hover:text-orange-800 border border-orange-200 px-2 py-1 rounded"
          >
            {{ $t('platform.suspend') }}
          </button>
          <button
            v-else
            @click="activate"
            class="text-xs text-green-600 hover:text-green-800 border border-green-200 px-2 py-1 rounded"
          >
            {{ $t('common.activate') }}
          </button>
          <button @click="impersonate" class="btn-primary text-xs">
            <i class="fa-solid fa-user-secret mr-1"></i> {{ $t('platform.sign_in_as') }}
          </button>
        </div>
      </div>

      <!-- Info -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 gap-4 text-sm">
        <div>
          <p class="text-xs text-gray-500">ID</p>
          <p class="font-mono text-gray-900">{{ tenant.id }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('platform.domain') }}</p>
          <p class="text-gray-900">{{ tenant.domain }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Plan</p>
          <p class="text-gray-900">{{ tenant.plan?.name ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('platform.registered') }}</p>
          <p class="text-gray-900">{{ formatDate(tenant.created_at) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('platform.users') }}</p>
          <p class="text-gray-900">{{ tenant.users_count ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('common.tasks') }}</p>
          <p class="text-gray-900">{{ tenant.tasks_count ?? '—' }}</p>
        </div>
      </div>

      <!-- Plan change -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('platform.change_plan') }}</h2>
        <div class="flex items-center gap-3">
          <select v-model="selectedPlan" class="input flex-1">
            <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <button @click="changePlan" class="btn-primary text-sm">{{ $t('common.change') }}</button>
        </div>
      </div>

      <!-- Danger -->
      <div class="bg-red-50 border border-red-200 rounded-xl p-5">
        <h2 class="text-sm font-semibold text-red-700 mb-3">{{ $t('platform.danger_zone') }}</h2>
        <button @click="deleteTenant" class="text-sm text-red-600 hover:text-red-800 font-medium">
          <i class="fa-solid fa-trash mr-1"></i> {{ $t('platform.delete_the_tenant_and_everything_in') }}
        </button>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'

const props = defineProps({
  tenant: Object,
  plans: { type: Array, default: () => [] },
})

const selectedPlan = ref(props.tenant.plan_id ?? null)

const activate = () => router.post(route('landlord.tenants.activate', props.tenant.id))
const suspend = () => {
  if (confirm(t('platform.suspend_this_tenant'))) router.post(route('landlord.tenants.suspend', props.tenant.id))
}
const impersonate = () => router.post(route('landlord.tenants.impersonate', props.tenant.id))
const changePlan = () => router.put(route('landlord.tenants.update', props.tenant.id), { plan_id: selectedPlan.value })
const deleteTenant = () => {
  if (
    confirm(t('platform.deleting_a_tenant_cannot_be_undone')) &&
    prompt(t('platform.type_delete')) === t('common.delete_2')
  ) {
    router.delete(route('landlord.tenants.destroy', props.tenant.id))
  }
}
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
