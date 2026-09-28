<template>
  <ManagerLayout :title="isEdit ? 'Edytuj klienta' : 'Nowy klient'">
    <div class="max-w-2xl mx-auto space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.clients.index')" class="btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i>
          </Link>
          <h1 class="page-title">{{ isEdit ? 'Edytuj klienta' : 'Nowy klient' }}</h1>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="card p-6 space-y-5">
          <h3 class="section-title">{{ $t('crm.company_details') }}</h3>

          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="label">{{ $t('common.company_name') }}</label>
              <input
                v-model="form.company_name"
                type="text"
                class="input"
                :class="{ 'input-error': form.errors.company_name }"
                :placeholder="$t('common.acme_ltd')"
              />
              <p v-if="form.errors.company_name" class="form-error">{{ form.errors.company_name }}</p>
            </div>
            <div>
              <label class="label">{{ $t('common.full_name') }}</label>
              <input
                v-model="form.name"
                type="text"
                class="input"
                :class="{ 'input-error': form.errors.name }"
                :placeholder="$t('common.jane_cooper')"
              />
              <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
            </div>
            <div>
              <label class="label">NIP</label>
              <input v-model="form.nip" type="text" class="input" placeholder="1234567890" />
            </div>
            <div>
              <label class="label">E-mail</label>
              <input v-model="form.email" type="email" class="input" :class="{ 'input-error': form.errors.email }" />
              <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
            </div>
            <div>
              <label class="label">{{ $t('common.phone') }}</label>
              <input v-model="form.phone" type="text" class="input" />
            </div>
            <div class="col-span-2">
              <label class="label">{{ $t('crm.website') }}</label>
              <input v-model="form.website" type="url" class="input" placeholder="https://..." />
            </div>
          </div>
        </div>

        <div class="card p-6 space-y-5">
          <h3 class="section-title">{{ $t('crm.address') }}</h3>
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="label">{{ $t('crm.street_and_number') }}</label>
              <input v-model="form.address" type="text" class="input" />
            </div>
            <div>
              <label class="label">{{ $t('crm.postcode') }}</label>
              <input v-model="form.postal_code" type="text" class="input" placeholder="00-000" />
            </div>
            <div>
              <label class="label">{{ $t('common.city') }}</label>
              <input v-model="form.city" type="text" class="input" />
            </div>
            <div>
              <label class="label">{{ $t('crm.country') }}</label>
              <input v-model="form.country" type="text" class="input" :placeholder="$t('crm.poland')" />
            </div>
          </div>
        </div>

        <div class="card p-6 space-y-5">
          <h3 class="section-title">{{ $t('crm.extra') }}</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">{{ $t('common.industry') }}</label>
              <input v-model="form.industry" type="text" class="input" :placeholder="$t('common.it_marketing')" />
            </div>
            <div>
              <label class="label">{{ $t('common.status') }}</label>
              <select v-model="form.status" class="select">
                <option value="active">{{ $t('common.active') }}</option>
                <option value="inactive">{{ $t('common.inactive') }}</option>
                <option value="prospect">{{ $t('crm.prospect') }}</option>
              </select>
            </div>
            <div class="col-span-2">
              <label class="label">{{ $t('common.internal_notes') }}</label>
              <textarea v-model="form.notes" class="textarea" rows="3"></textarea>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <Link :href="route('tenant.manager.clients.index')" class="btn-secondary">{{ $t('common.cancel') }}</Link>
          <button type="submit" :disabled="form.processing" class="btn-primary">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
            {{ isEdit ? 'Zapisz zmiany' : $t('common.create_client') }}
          </button>
        </div>
      </form>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  client: { type: Object, default: null },
})

const isEdit = computed(() => !!props.client)

const form = useForm({
  company_name: props.client?.company_name ?? '',
  name: props.client?.name ?? '',
  nip: props.client?.nip ?? '',
  email: props.client?.email ?? '',
  phone: props.client?.phone ?? '',
  website: props.client?.website ?? '',
  address: props.client?.address ?? '',
  postal_code: props.client?.postal_code ?? '',
  city: props.client?.city ?? '',
  country: props.client?.country ?? 'Polska',
  industry: props.client?.industry ?? '',
  status: props.client?.status ?? 'active',
  notes: props.client?.notes ?? '',
})

const submit = () => {
  if (isEdit.value) {
    form.put(route('tenant.manager.clients.update', props.client.id))
  } else {
    form.post(route('tenant.manager.clients.store'))
  }
}
</script>
