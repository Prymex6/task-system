<template>
  <LandlordLayout :title="plan ? $t('common.edit_plan') : $t('common.new_plan')">
    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">
          {{ plan ? $t('common.edit_plan') : $t('common.new_subscription_plan') }}
        </h1>

        <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6 space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('platform.plan_name') }}</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            />
            <p v-if="errors.name" class="text-red-600 text-xs mt-1">{{ errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('platform.price_per_year') }}</label>
            <input
              v-model="form.price"
              type="number"
              min="0"
              step="0.01"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
              :placeholder="$t('common.e_g_480')"
            />
            <p class="text-xs text-gray-500 mt-1">
              {{ $t('platform.leave_empty_for_a_free_plan') }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              $t('common.max_projects_per_month')
            }}</label>
            <input
              v-model="form.max_orders_per_month"
              type="number"
              min="0"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
              :placeholder="$t('platform.leave_empty_for_no_limit')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('platform.features') }}</label>
            <div v-for="(feature, i) in form.features" :key="i" class="flex gap-2 mb-2">
              <input
                v-model="form.features[i]"
                type="text"
                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm"
                :placeholder="$t('platform.e_g_sms_notifications')"
              />
              <button type="button" @click="form.features.splice(i, 1)" class="text-red-500 hover:text-red-700 px-2">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
            <button type="button" @click="form.features.push('')" class="text-blue-600 hover:text-blue-800 text-sm">
              {{ $t('platform.add_feature') }}
            </button>
          </div>

          <div class="flex items-center gap-3">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4 text-blue-600" />
            <label for="is_active" class="text-sm text-gray-700">{{
              $t('platform.plan_is_active_and_offered_to')
            }}</label>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
              {{ plan ? $t('common.save_changes') : $t('common.create_plan') }}
            </button>
            <Link
              :href="route('landlord.plans.index')"
              class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg"
            >
              {{ $t('common.cancel') }}
            </Link>
          </div>
        </form>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'

const props = defineProps({
  plan: { type: Object, default: null },
})

const errors = usePage().props.errors || {}

const form = reactive({
  name: props.plan?.name ?? '',
  price: props.plan?.price ?? '',
  max_orders_per_month: props.plan?.max_orders_per_month ?? '',
  features: props.plan?.features ? [...props.plan.features] : [],
  is_active: props.plan?.is_active ?? true,
})

function submit() {
  const data = {
    ...form,
    price: form.price === '' ? null : form.price,
    max_orders_per_month: form.max_orders_per_month === '' ? null : form.max_orders_per_month,
    features: form.features.filter((f) => f.trim()),
  }

  if (props.plan) {
    router.put(route('landlord.plans.update', props.plan.id), data)
  } else {
    router.post(route('landlord.plans.store'), data)
  }
}
</script>
