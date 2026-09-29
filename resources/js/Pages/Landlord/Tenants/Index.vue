<template>
  <LandlordLayout :title="$t('common.workspaces')">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- One-time password reveal banner -->
        <div v-if="generatedPassword && showPassword" class="mb-6 bg-yellow-50 border border-yellow-300 rounded-lg p-4">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-semibold text-yellow-800 mb-1">
                {{ $t('platform.the_manager_account_is_ready_write') }}
              </p>
              <p class="text-xs text-yellow-700 mb-3">
                {{ $t('platform.e_mail') }} <strong>{{ generatedEmail }}</strong>
                {{ $t('platform.this_password_will_not_be_shown') }}
              </p>
              <div class="flex items-center gap-3">
                <code
                  class="bg-yellow-100 border border-yellow-300 rounded px-3 py-1 text-base font-mono tracking-widest text-yellow-900"
                  >{{ generatedPassword }}</code
                >
                <button
                  @click="copyPassword"
                  class="text-xs px-3 py-1 bg-yellow-700 hover:bg-yellow-800 text-white rounded transition"
                >
                  {{ copied ? 'Skopiowano!' : 'Kopiuj' }}
                </button>
              </div>
            </div>
            <button
              @click="showPassword = false"
              class="text-yellow-500 hover:text-yellow-700 ml-4 text-lg leading-none"
            >
              &times;
            </button>
          </div>
        </div>

        <div class="flex justify-end mb-8">
          <Link
            :href="route('landlord.tenants.create')"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
          >
            {{ $t('common.add_workspace_2') }}
          </Link>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('common.name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('platform.domain') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('common.version') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('common.status') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('platform.licence_until') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('platform.created') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="tenant in tenants.data" :key="tenant.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ tenant.name }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-500">
                    {{ tenant.domains?.[0]?.domain || $t('settings.none') }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="tenant.version === 'test' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ tenant.version === 'test' ? 'Testowa' : 'Stabilna' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="tenant.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ tenant.status === 'active' ? 'Aktywny' : 'Nieaktywny' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span
                    v-if="tenant.license_ends_at"
                    :class="isLicenseExpired(tenant) ? 'text-red-600 font-semibold' : 'text-gray-600'"
                  >
                    {{ formatDate(tenant.license_ends_at) }}
                    <span v-if="isLicenseExpired(tenant)" class="text-xs ml-1">{{ $t('platform.expired') }}</span>
                  </span>
                  <span v-else class="text-gray-400 text-xs">{{ $t('common.no_end_date') }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ new Date(tenant.created_at).toLocaleDateString('pl-PL') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <Link
                    v-if="tenant.status === 'suspended'"
                    :href="route('landlord.tenants.activate', tenant.id)"
                    method="post"
                    as="button"
                    class="text-green-600 hover:text-green-900 mr-3"
                  >
                    {{ $t('common.activate') }}
                  </Link>
                  <Link
                    v-else
                    :href="route('landlord.tenants.suspend', tenant.id)"
                    method="post"
                    as="button"
                    class="text-orange-600 hover:text-orange-900 mr-3"
                  >
                    {{ $t('platform.deactivate') }}
                  </Link>
                  <Link
                    :href="route('landlord.tenants.edit', tenant.id)"
                    class="text-blue-600 hover:text-blue-900 mr-3"
                  >
                    {{ $t('common.edit') }}
                  </Link>
                  <button @click="impersonate(tenant.id)" class="text-purple-600 hover:text-purple-900 mr-3">
                    {{ $t('platform.sign_in') }}
                  </button>
                  <button class="text-red-600 hover:text-red-900" @click="confirmDelete(tenant)">
                    {{ $t('common.delete') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Delete confirmation modal -->
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $t('common.delete_workspace') }}</h3>
            <p class="text-gray-600 mb-1">
              {{ $t('common.delete_this_workspace') }} <strong>{{ deleteTarget.name }}</strong
              >?
            </p>
            <p class="text-red-600 text-sm mb-6">
              {{ $t('common.this_cannot_be_undone_the_whole') }}
            </p>
            <div class="flex justify-end gap-3">
              <button
                @click="deleteTarget = null"
                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
              >
                {{ $t('common.cancel') }}
              </button>
              <Link
                :href="route('landlord.tenants.destroy', deleteTarget.id)"
                method="delete"
                as="button"
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                @click="deleteTarget = null"
              >
                {{ $t('platform.delete_permanently') }}
              </Link>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="tenants.links.length > 3" class="mt-6 flex justify-center">
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <Link
              v-for="(link, index) in tenants.links"
              :key="index"
              :href="link.url"
              :class="{
                'bg-blue-600 text-white': link.active,
                'bg-white text-gray-700 hover:bg-gray-50': !link.active,
                'cursor-not-allowed opacity-50': !link.url,
              }"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium"
              v-html="link.label"
            />
          </nav>
        </div>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'

defineProps({
  tenants: Object,
})

const page = usePage()

function impersonate(tenantId) {
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = route('landlord.tenants.impersonate', tenantId)
  form.target = '_blank'
  const input = document.createElement('input')
  input.type = 'hidden'
  input.name = '_token'
  input.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  form.appendChild(input)
  document.body.appendChild(form)
  form.submit()
  document.body.removeChild(form)
}
const showPassword = ref(true)
const copied = ref(false)
const deleteTarget = ref(null)

function formatDate(dt) {
  if (!dt) return ''
  return new Date(dt).toLocaleDateString('pl-PL')
}

function isLicenseExpired(tenant) {
  if (!tenant.license_ends_at) return false
  return new Date(tenant.license_ends_at) < new Date()
}

const generatedPassword = page.props.flash?.generated_password
const generatedEmail = page.props.flash?.generated_password_email

function copyPassword() {
  navigator.clipboard.writeText(generatedPassword).then(() => {
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  })
}

function confirmDelete(tenant) {
  deleteTarget.value = tenant
}
</script>
