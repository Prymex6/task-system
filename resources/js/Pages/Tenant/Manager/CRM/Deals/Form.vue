<template>
  <ManagerLayout :title="isEdit ? 'Edycja szansy' : $t('common.new_deal')">
    <form class="max-w-3xl space-y-5" @submit.prevent="submit">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? $t('crm.edit_deal') : $t('common.new_deal') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('crm.a_specific_piece_of_business_with') }}</p>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5 grid gap-4 sm:grid-cols-2">
        <FormField :label="$t('common.title')" :error="form.errors.title" required class="sm:col-span-2">
          <input v-model="form.title" type="text" class="input" required />
        </FormField>
        <FormField :label="$t('common.client')" :error="form.errors.client_id">
          <select v-model="form.client_id" class="input">
            <option :value="null">{{ $t('common.none') }}</option>
            <option v-for="c in clients" :key="c.id" :value="c.id">
              {{ c.company_name || c.name }}
            </option>
          </select>
        </FormField>
        <FormField :label="$t('common.stage')" :error="form.errors.deal_stage_id" required>
          <select v-model="form.deal_stage_id" class="input" required>
            <option v-for="s in stages" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </FormField>
        <FormField :label="$t('common.value')" :error="form.errors.value">
          <input v-model="form.value" type="number" step="0.01" min="0" class="input" />
        </FormField>
        <FormField :label="$t('common.currency')" :error="form.errors.currency">
          <input v-model="form.currency" type="text" maxlength="5" class="input" />
        </FormField>
        <FormField :label="$t('crm.expected_close')" :error="form.errors.expected_close_date">
          <input v-model="form.expected_close_date" type="date" class="input" />
        </FormField>
        <FormField :label="$t('common.notes')" :error="form.errors.notes" class="sm:col-span-2">
          <textarea v-model="form.notes" rows="4" class="input"></textarea>
        </FormField>
      </div>

      <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary" :disabled="form.processing">
          {{ isEdit ? $t('common.save_changes') : $t('common.add_deal') }}
        </button>
        <Link :href="route('tenant.manager.deals.index')" class="btn-ghost">{{ $t('common.cancel') }}</Link>
      </div>
    </form>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import FormField from '@/Components/Manager/FormField.vue'

const props = defineProps({
  deal: { type: Object, default: null },
  clients: { type: Array, default: () => [] },
  stages: { type: Array, default: () => [] },
})

const isEdit = computed(() => Boolean(props.deal))

const form = useForm({
  title: props.deal?.title ?? '',
  client_id: props.deal?.client_id ?? null,
  deal_stage_id: props.deal?.deal_stage_id ?? props.stages[0]?.id ?? null,
  value: props.deal?.value ?? '',
  currency: props.deal?.currency ?? 'PLN',
  expected_close_date: props.deal?.expected_close_date ?? '',
  notes: props.deal?.notes ?? '',
})

const submit = () => {
  if (isEdit.value) {
    form.put(route('tenant.manager.deals.update', props.deal.id))
  } else {
    form.post(route('tenant.manager.deals.store'))
  }
}
</script>
