<template>
  <LandlordLayout :title="$t('platform.workspace_leads')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('platform.workspace_leads') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ $t('platform.companies_worth_approaching_about_the_platform') }}
          </p>
        </div>
        <button @click="showCreate = true" class="btn-primary text-sm">
          <i class="fa-solid fa-plus mr-1"></i> {{ $t('platform.add_lead') }}
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex gap-3">
        <input
          v-model="searchQ"
          @input="apply"
          :placeholder="$t('platform.search_by_company_or_e_mail')"
          class="input-sm flex-1"
        />
        <select v-model="contacted" @change="apply" class="input-sm">
          <option value="">{{ $t('common.all') }}</option>
          <option value="yes">{{ $t('platform.contacted') }}</option>
          <option value="no">{{ $t('platform.not_contacted') }}</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.company') }}</th>
              <th class="th">{{ $t('platform.contact_2') }}</th>
              <th class="th">{{ $t('common.industry') }}</th>
              <th class="th">{{ $t('common.city') }}</th>
              <th class="th text-center">{{ $t('common.contacted') }}</th>
              <th class="th">{{ $t('common.source') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="lead in leads.data"
              :key="lead.id"
              class="hover:bg-gray-50 cursor-pointer"
              @click="selected = lead"
            >
              <td class="td">
                <p class="font-medium text-gray-900">{{ lead.company_name }}</p>
                <p v-if="lead.website" class="text-xs text-indigo-500 truncate max-w-[160px]">{{ lead.website }}</p>
              </td>
              <td class="td">
                <p class="text-gray-900">{{ lead.contact_name ?? '—' }}</p>
                <p v-if="lead.email" class="text-xs text-gray-400">{{ lead.email }}</p>
              </td>
              <td class="td text-gray-600">{{ lead.industry ?? '—' }}</td>
              <td class="td text-gray-600">{{ lead.city ?? '—' }}</td>
              <td class="td text-center">
                <button
                  @click.stop="toggle(lead)"
                  class="text-xs px-2 py-0.5 rounded-full font-medium transition-colors"
                  :class="
                    lead.contacted_at ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500 hover:bg-green-50'
                  "
                >
                  {{ lead.contacted_at ? $t('common.yes') : $t('common.no') }}
                </button>
              </td>
              <td class="td text-gray-400 text-xs">{{ lead.source ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="!leads.data?.length" class="py-16 text-center">
          <i class="fa-solid fa-building-circle-arrow-right text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('platform.no_leads') }}</p>
        </div>
      </div>

      <Pagination :links="leads.links" />

      <!-- Detail side panel -->
      <div
        v-if="selected"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="selected = null"
      >
        <div class="bg-white rounded-xl shadow-xl w-[460px] p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">{{ selected.company_name }}</h3>
            <button @click="selected = null" class="text-gray-400 hover:text-gray-600">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <p class="text-xs text-gray-500">{{ $t('platform.contact_2') }}</p>
              <p>{{ selected.contact_name ?? '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Email</p>
              <p>{{ selected.email ?? '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">{{ $t('common.phone') }}</p>
              <p>{{ selected.phone ?? '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">WWW</p>
              <a :href="selected.website" target="_blank" class="text-indigo-600 truncate block">{{
                selected.website ?? '—'
              }}</a>
            </div>
            <div>
              <p class="text-xs text-gray-500">{{ $t('common.industry') }}</p>
              <p>{{ selected.industry ?? '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">{{ $t('common.city') }}</p>
              <p>{{ selected.city ?? '—' }}</p>
            </div>
            <div v-if="selected.notes" class="col-span-2">
              <p class="text-xs text-gray-500 mb-1">{{ $t('common.notes') }}</p>
              <p class="text-gray-700 whitespace-pre-wrap">{{ selected.notes }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Create modal -->
      <div
        v-if="showCreate"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="showCreate = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-96 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">{{ $t('platform.new_workspace_lead') }}</h3>
          <form @submit.prevent="create" class="space-y-3">
            <div>
              <label class="label">{{ $t('common.company_name') }}</label
              ><input v-model="form.company_name" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('platform.contact') }}</label
              ><input v-model="form.contact_name" class="input" />
            </div>
            <div><label class="label">Email</label><input v-model="form.email" type="email" class="input" /></div>
            <div>
              <label class="label">{{ $t('common.phone') }}</label
              ><input v-model="form.phone" class="input" />
            </div>
            <div>
              <label class="label">{{ $t('common.website') }}</label
              ><input v-model="form.website" class="input" />
            </div>
            <div>
              <label class="label">{{ $t('common.industry') }}</label
              ><input v-model="form.industry" class="input" />
            </div>
            <div>
              <label class="label">{{ $t('common.city') }}</label
              ><input v-model="form.city" class="input" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="showCreate = false" class="btn-ghost text-sm">
                {{ $t('common.cancel') }}
              </button>
              <button type="submit" class="btn-primary text-sm">{{ $t('common.add') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  leads: Object,
  filters: Object,
})

const searchQ = ref(props.filters?.search ?? '')
const contacted = ref(props.filters?.contacted ?? '')
const showCreate = ref(false)
const selected = ref(null)
const form = reactive({ company_name: '', contact_name: '', email: '', phone: '', website: '', industry: '', city: '' })

const apply = () =>
  router.get(
    route('landlord.workspace-leads.index'),
    { search: searchQ.value, contacted: contacted.value },
    { preserveState: true, replace: true },
  )
const create = () =>
  router.post(route('landlord.workspace-leads.index'), form, {
    onSuccess: () => {
      showCreate.value = false
    },
  })
const toggle = (lead) => router.post(route('landlord.workspace-leads.toggle-contacted'), { id: lead.id })
</script>
