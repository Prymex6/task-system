<template>
  <ManagerLayout :title="isEdit ? $t('common.edit_contract') : $t('manager.new_contract')">
    <div class="max-w-3xl mx-auto space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.contracts.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <h1 class="page-title">{{ isEdit ? $t('common.edit_contract') : $t('manager.new_contract') }}</h1>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="card p-6 space-y-4">
          <h3 class="section-title">{{ $t('common.basics') }}</h3>
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="label">{{ $t('manager.contract_title') }} <span class="text-red-500">*</span></label>
              <input
                v-model="form.title"
                type="text"
                class="input"
                :class="{ 'input-error': form.errors.title }"
                required
              />
              <p v-if="form.errors.title" class="form-error">{{ form.errors.title }}</p>
            </div>
            <div>
              <label class="label">{{ $t('common.client') }} <span class="text-red-500">*</span></label>
              <select v-model="form.client_id" class="select" required>
                <option value="">{{ $t('common.choose') }}</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company_name || c.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('common.project') }}</label>
              <select v-model="form.project_id" class="select">
                <option value="">{{ $t('common.none') }}</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('common.start_date') }}</label>
              <input v-model="form.start_date" type="date" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('manager.end_date') }}</label>
              <input v-model="form.end_date" type="date" class="input" />
            </div>
            <div>
              <label class="label">{{ $t('common.value') }}</label>
              <input v-model="form.value" type="number" step="0.01" min="0" class="input" />
            </div>
            <div>
              <label class="label">{{ $t('common.currency') }}</label>
              <select v-model="form.currency" class="select">
                <option value="PLN">PLN</option>
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('common.status') }}</label>
              <select v-model="form.status" class="select">
                <option value="draft">{{ $t('common.draft') }}</option>
                <option value="active">{{ $t('common.active_2') }}</option>
                <option value="signed">{{ $t('manager.signed') }}</option>
                <option value="expired">{{ $t('common.expired') }}</option>
                <option value="cancelled">{{ $t('common.cancelled_2') }}</option>
              </select>
            </div>
          </div>
        </div>

        <div class="card p-6">
          <label class="label">{{ $t('manager.contract_body') }}</label>
          <textarea
            v-model="form.content"
            class="textarea font-mono text-xs"
            rows="12"
            :placeholder="$t('manager.the_terms')"
          ></textarea>
        </div>

        <div class="card p-6">
          <label class="label">{{ $t('common.internal_notes') }}</label>
          <textarea v-model="form.notes" class="textarea" rows="3"></textarea>
        </div>

        <div class="flex justify-end gap-3">
          <Link :href="route('tenant.manager.contracts.index')" class="btn-secondary">{{ $t('common.cancel') }}</Link>
          <button type="submit" :disabled="form.processing" class="btn-primary">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
            {{ isEdit ? $t('common.save_changes') : $t('common.create_contract') }}
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
  contract: { type: Object, default: null },
  clients: { type: Array, default: () => [] },
  projects: { type: Array, default: () => [] },
})

const isEdit = computed(() => !!props.contract)

const form = useForm({
  title: props.contract?.title ?? '',
  client_id: props.contract?.client_id ?? '',
  project_id: props.contract?.project_id ?? '',
  start_date: props.contract?.start_date ?? new Date().toISOString().slice(0, 10),
  end_date: props.contract?.end_date ?? '',
  value: props.contract?.value ?? '',
  currency: props.contract?.currency ?? 'PLN',
  status: props.contract?.status ?? 'draft',
  content: props.contract?.content ?? '',
  notes: props.contract?.notes ?? '',
})

const submit = () => {
  if (isEdit.value) {
    form.put(route('tenant.manager.contracts.update', props.contract.id))
  } else {
    form.post(route('tenant.manager.contracts.store'))
  }
}
</script>
