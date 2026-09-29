<template>
  <ManagerLayout :title="client.company_name || client.name">
    <div class="space-y-6">
      <!-- Header -->
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.clients.index')" class="btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i>
          </Link>
          <div>
            <h1 class="page-title">{{ client.company_name || client.name }}</h1>
            <p class="page-subtitle" v-if="client.industry">{{ client.industry }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <Link :href="route('tenant.manager.clients.edit', client.id)" class="btn-secondary">
            <i class="fa-solid fa-pen"></i> {{ $t('common.edit') }}
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: details -->
        <div class="space-y-4">
          <div class="card p-5 space-y-3">
            <h3 class="section-title">{{ $t('crm.contact_details') }}</h3>
            <div v-if="client.email" class="flex items-center gap-2 text-sm text-gray-600">
              <i class="fa-solid fa-envelope w-4 text-gray-400"></i>
              <a :href="`mailto:${client.email}`" class="hover:text-indigo-600">{{ client.email }}</a>
            </div>
            <div v-if="client.phone" class="flex items-center gap-2 text-sm text-gray-600">
              <i class="fa-solid fa-phone w-4 text-gray-400"></i>
              {{ client.phone }}
            </div>
            <div v-if="client.website" class="flex items-center gap-2 text-sm text-gray-600">
              <i class="fa-solid fa-globe w-4 text-gray-400"></i>
              <a :href="client.website" target="_blank" class="hover:text-indigo-600 truncate">{{ client.website }}</a>
            </div>
            <div v-if="client.address" class="flex items-start gap-2 text-sm text-gray-600">
              <i class="fa-solid fa-location-dot w-4 text-gray-400 mt-0.5"></i>
              <span>{{ client.address }}<br v-if="client.city" />{{ client.postal_code }} {{ client.city }}</span>
            </div>
            <div v-if="client.nip" class="flex items-center gap-2 text-sm text-gray-600">
              <i class="fa-solid fa-building w-4 text-gray-400"></i>
              NIP: {{ client.nip }}
            </div>
          </div>

          <!-- Contacts / Portal users -->
          <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="section-title mb-0">{{ $t('crm.portal_contacts') }}</h3>
              <button @click="showAddContact = !showAddContact" class="btn-ghost btn-sm">
                <i class="fa-solid fa-plus"></i>
              </button>
            </div>
            <form v-if="showAddContact" @submit.prevent="addContact" class="mb-3 space-y-2">
              <input
                v-model="contactForm.name"
                type="text"
                :placeholder="$t('common.full_name')"
                class="input input-sm"
                required
              />
              <input v-model="contactForm.email" type="email" placeholder="E-mail" class="input input-sm" required />
              <input
                v-model="contactForm.password"
                type="password"
                :placeholder="$t('common.password')"
                class="input input-sm"
                required
              />
              <button type="submit" :disabled="contactForm.processing" class="btn-primary btn-sm w-full">
                {{ $t('common.add') }}
              </button>
            </form>
            <div v-if="client.contacts?.length" class="space-y-2">
              <div v-for="contact in client.contacts" :key="contact.id" class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-medium text-gray-800">{{ contact.name }}</div>
                  <div class="text-xs text-gray-400">{{ contact.email }}</div>
                </div>
                <button @click="removeContact(contact.id)" class="btn-ghost btn-sm text-red-500">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </div>
            </div>
            <p v-else class="text-sm text-gray-400">{{ $t('crm.no_portal_contacts') }}</p>
          </div>
        </div>

        <!-- Right: projects, invoices, notes -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Projects -->
          <div class="card">
            <div class="card-header flex items-center justify-between">
              <h3 class="section-title mb-0">{{ $t('crm.projects') }}{{ client.projects?.length ?? 0 }})</h3>
              <Link
                :href="route('tenant.manager.projects.create') + '?client_id=' + client.id"
                class="btn-ghost btn-sm"
              >
                <i class="fa-solid fa-plus"></i> {{ $t('crm.new_project') }}
              </Link>
            </div>
            <div class="divide-y divide-gray-100">
              <div v-if="!client.projects?.length" class="px-6 py-6 text-center text-sm text-gray-400">
                {{ $t('common.no_projects') }}
              </div>
              <div
                v-for="p in client.projects"
                :key="p.id"
                class="px-6 py-3 flex items-center justify-between hover:bg-gray-50"
              >
                <Link
                  :href="route('tenant.manager.projects.show', p.id)"
                  class="text-sm text-gray-800 hover:text-indigo-600 font-medium"
                >
                  {{ p.name }}
                </Link>
                <StatusBadge :status="p.status" />
              </div>
            </div>
          </div>

          <!-- Invoices -->
          <div class="card">
            <div class="card-header flex items-center justify-between">
              <h3 class="section-title mb-0">{{ $t('crm.invoices') }}{{ client.invoices?.length ?? 0 }})</h3>
              <Link
                :href="route('tenant.manager.invoices.create') + '?client_id=' + client.id"
                class="btn-ghost btn-sm"
              >
                <i class="fa-solid fa-plus"></i> {{ $t('crm.new_invoice') }}
              </Link>
            </div>
            <div class="divide-y divide-gray-100">
              <div v-if="!client.invoices?.length" class="px-6 py-6 text-center text-sm text-gray-400">
                {{ $t('common.no_invoices') }}
              </div>
              <div
                v-for="inv in client.invoices"
                :key="inv.id"
                class="px-6 py-3 flex items-center justify-between hover:bg-gray-50"
              >
                <div>
                  <Link
                    :href="route('tenant.manager.invoices.show', inv.id)"
                    class="text-sm font-medium text-gray-800 hover:text-indigo-600"
                  >
                    {{ inv.number }}
                  </Link>
                  <div class="text-xs text-gray-400">{{ inv.issue_date }}</div>
                </div>
                <div class="text-right">
                  <div class="text-sm font-semibold">{{ formatMoney(inv.total) }}</div>
                  <StatusBadge :status="inv.status" />
                </div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div class="card p-5">
            <h3 class="section-title">{{ $t('common.notes') }}</h3>
            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ client.notes || '—' }}</p>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  client: Object,
})

const showAddContact = ref(false)

const contactForm = useForm({
  name: '',
  email: '',
  password: '',
})

const addContact = () => {
  contactForm.post(route('tenant.manager.clients.contacts.add', props.client.id), {
    onSuccess: () => {
      contactForm.reset()
      showAddContact.value = false
    },
  })
}

const removeContact = (contactId) => {
  if (!confirm(t('crm.remove_portal_access_for_this_contact'))) return
  useForm({}).delete(route('tenant.manager.clients.contacts.remove', [props.client.id, contactId]))
}

const formatMoney = (val) =>
  new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(val ?? 0)
</script>
