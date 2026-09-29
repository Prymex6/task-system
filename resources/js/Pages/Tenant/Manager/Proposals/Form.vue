<template>
  <ManagerLayout :title="isEdit ? $t('common.edit_proposal') : $t('manager.new_proposal')">
    <div class="max-w-3xl mx-auto space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.proposals.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <h1 class="page-title">{{ isEdit ? $t('common.edit_proposal') : $t('manager.new_proposal') }}</h1>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="card p-6 space-y-4">
          <h3 class="section-title">{{ $t('common.basics') }}</h3>
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="label">{{ $t('common.title') }} <span class="text-red-500">*</span></label>
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
              <label class="label">{{ $t('common.valid_until') }}</label>
              <input v-model="form.valid_until" type="date" class="input" />
            </div>
          </div>
        </div>

        <div class="card p-6">
          <label class="label">{{ $t('manager.proposal_body') }}</label>
          <textarea
            v-model="form.content"
            class="textarea"
            rows="10"
            :placeholder="$t('manager.what_you_are_proposing')"
          ></textarea>
        </div>

        <div class="card p-6">
          <label class="label">{{ $t('common.internal_notes') }}</label>
          <textarea v-model="form.notes" class="textarea" rows="3"></textarea>
        </div>

        <div class="flex justify-end gap-3">
          <Link :href="route('tenant.manager.proposals.index')" class="btn-secondary">{{ $t('common.cancel') }}</Link>
          <button type="submit" :disabled="form.processing" class="btn-primary">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
            {{ isEdit ? $t('common.save_changes') : $t('common.create_proposal') }}
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
  proposal: { type: Object, default: null },
  clients: { type: Array, default: () => [] },
  projects: { type: Array, default: () => [] },
})

const isEdit = computed(() => !!props.proposal)

const form = useForm({
  title: props.proposal?.title ?? '',
  client_id: props.proposal?.client_id ?? '',
  project_id: props.proposal?.project_id ?? '',
  value: props.proposal?.value ?? '',
  currency: props.proposal?.currency ?? 'PLN',
  valid_until: props.proposal?.valid_until ?? '',
  content: props.proposal?.content ?? '',
  notes: props.proposal?.notes ?? '',
})

const submit = () => {
  if (isEdit.value) {
    form.put(route('tenant.manager.proposals.update', props.proposal.id))
  } else {
    form.post(route('tenant.manager.proposals.store'))
  }
}
</script>
