<template>
  <ManagerLayout :title="$t('hr.performance_reviews')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('hr.performance_reviews') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('hr.how_the_team_is_doing') }}</p>
        </div>
        <button @click="showCreate = true" class="btn-primary text-sm">
          <i class="fa-solid fa-plus mr-1"></i> {{ $t('hr.new_review') }}
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.employee') }}</th>
              <th class="th">{{ $t('hr.period') }}</th>
              <th class="th text-center">{{ $t('hr.rating') }}</th>
              <th class="th">{{ $t('hr.reviewer') }}</th>
              <th class="th">{{ $t('common.date') }}</th>
              <th class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="review in reviews.data" :key="review.id" class="hover:bg-gray-50">
              <td class="td">
                <div class="flex items-center gap-2">
                  <div
                    class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold"
                  >
                    {{ review.user?.name?.charAt(0) }}
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ review.user?.name }}</span>
                </div>
              </td>
              <td class="td text-sm text-gray-700">{{ review.period }}</td>
              <td class="td text-center">
                <div class="flex items-center justify-center gap-0.5">
                  <i
                    v-for="i in 5"
                    :key="i"
                    class="fa-solid fa-star text-sm"
                    :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-200'"
                  ></i>
                </div>
                <span class="text-xs text-gray-500">{{ review.rating }}/5</span>
              </td>
              <td class="td text-sm text-gray-600">{{ review.reviewer?.name }}</td>
              <td class="td text-sm text-gray-500">{{ formatDate(review.created_at) }}</td>
              <td class="td text-right">
                <button @click="selected = review" class="text-xs text-indigo-600 hover:text-indigo-800">
                  {{ $t('common.details') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!reviews.data?.length" class="py-16 text-center">
          <i class="fa-solid fa-star text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('hr.no_reviews_yet') }}</p>
        </div>
      </div>

      <Pagination :links="reviews.links" />

      <!-- Detail modal -->
      <div
        v-if="selected"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="selected = null"
      >
        <div class="bg-white rounded-xl shadow-xl w-[500px] p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">{{ selected.user?.name }} — {{ selected.period }}</h3>
            <button @click="selected = null" class="text-gray-400 hover:text-gray-600">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <div class="flex justify-center mb-4">
            <div class="flex gap-1">
              <i
                v-for="i in 5"
                :key="i"
                class="fa-solid fa-star text-2xl"
                :class="i <= selected.rating ? 'text-yellow-400' : 'text-gray-200'"
              ></i>
            </div>
          </div>
          <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ selected.comment }}</p>
        </div>
      </div>

      <!-- Create modal -->
      <div
        v-if="showCreate"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="showCreate = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-96 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">{{ $t('hr.new_performance_review') }}</h3>
          <form @submit.prevent="create" class="space-y-3">
            <div>
              <label class="label">{{ $t('common.employee') }}</label>
              <select v-model="form.user_id" class="input" required>
                <option value="">{{ $t('hr.choose') }}</option>
                <option v-for="u in staff" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('hr.period') }}</label>
              <input v-model="form.period" class="input" :placeholder="$t('common.e_g_q1_2026')" required />
            </div>
            <div>
              <label class="label">{{ $t('hr.rating_15') }}</label>
              <div class="flex gap-2">
                <button
                  v-for="i in 5"
                  :key="i"
                  type="button"
                  @click="form.rating = i"
                  class="w-10 h-10 rounded-lg border font-bold transition-colors text-sm"
                  :class="
                    form.rating === i
                      ? 'bg-yellow-400 border-yellow-400 text-white'
                      : 'border-gray-200 text-gray-500 hover:border-yellow-300'
                  "
                >
                  {{ i }}
                </button>
              </div>
            </div>
            <div>
              <label class="label">{{ $t('hr.comment') }}</label>
              <textarea v-model="form.comment" rows="4" class="input"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="showCreate = false" class="btn-ghost text-sm">
                {{ $t('common.cancel') }}
              </button>
              <button type="submit" class="btn-primary text-sm">{{ $t('hr.save_review') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  reviews: Object,
  staff: { type: Array, default: () => [] },
})

const showCreate = ref(false)
const selected = ref(null)
const form = reactive({ user_id: '', period: '', rating: 3, comment: '' })

const create = () => {
  router.post(route('tenant.manager.performance.store'), form, {
    onSuccess: () => {
      showCreate.value = false
    },
  })
}

const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
</script>
