<template>
  <ManagerLayout :title="$t('settings.settings_company')">
    <div class="max-w-2xl space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('settings.company_settings') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.the_workspace_details_printed_on_documents') }}</p>
      </div>

      <form @submit.prevent="save" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="label">{{ $t('common.company_name') }}</label>
            <input v-model="form.company_name" class="input" required />
          </div>
          <div>
            <label class="label">NIP</label>
            <input v-model="form.company_nip" class="input" />
          </div>
          <div>
            <label class="label">REGON</label>
            <input v-model="form.company_regon" class="input" />
          </div>
          <div class="col-span-2">
            <label class="label">{{ $t('crm.address') }}</label>
            <input v-model="form.company_address" class="input" />
          </div>
          <div>
            <label class="label">{{ $t('crm.postcode') }}</label>
            <input v-model="form.company_zip" class="input" />
          </div>
          <div>
            <label class="label">{{ $t('common.city') }}</label>
            <input v-model="form.company_city" class="input" />
          </div>
          <div>
            <label class="label">{{ $t('crm.country') }}</label>
            <input v-model="form.company_country" class="input" />
          </div>
          <div>
            <label class="label">{{ $t('common.phone') }}</label>
            <input v-model="form.company_phone" class="input" />
          </div>
          <div class="col-span-2">
            <label class="label">{{ $t('settings.company_e_mail') }}</label>
            <input v-model="form.company_email" type="email" class="input" />
          </div>
          <div class="col-span-2">
            <label class="label">{{ $t('common.website') }}</label>
            <input v-model="form.company_website" class="input" />
          </div>
          <div class="col-span-2">
            <label class="label">{{ $t('settings.document_footer') }}</label>
            <textarea
              v-model="form.invoice_footer"
              rows="3"
              class="input"
              :placeholder="$t('settings.e_g_bank_details_account_number')"
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button type="submit" class="btn-primary">{{ $t('common.save_changes') }}</button>
        </div>
      </form>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({ settings: Object })

const form = reactive({
  company_name: props.settings?.company_name ?? '',
  company_nip: props.settings?.company_nip ?? '',
  company_regon: props.settings?.company_regon ?? '',
  company_address: props.settings?.company_address ?? '',
  company_zip: props.settings?.company_zip ?? '',
  company_city: props.settings?.company_city ?? '',
  company_country: props.settings?.company_country ?? 'Polska',
  company_phone: props.settings?.company_phone ?? '',
  company_email: props.settings?.company_email ?? '',
  company_website: props.settings?.company_website ?? '',
  invoice_footer: props.settings?.invoice_footer ?? '',
})

const save = () => router.post(route('tenant.manager.settings.company.update'), form, { preserveScroll: true })
</script>
