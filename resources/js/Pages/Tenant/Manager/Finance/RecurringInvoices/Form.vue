<template>
  <ManagerLayout :title="isEdit ? 'Edycja zlecenia' : $t('common.new_recurring_invoice')">
    <form class="max-w-3xl space-y-5" @submit.prevent="submit">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEdit ? 'Edycja zlecenia' : $t('common.new_recurring_invoice') }}
        </h1>
        <p class="text-sm text-gray-500 mt-0.5">
          {{ $t('finance.the_lines_are_saved_as_a') }}
        </p>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5 grid gap-4 sm:grid-cols-2">
        <FormField :label="$t('common.title')" :error="form.errors.title" required class="sm:col-span-2">
          <input v-model="form.title" type="text" class="input" required />
        </FormField>
        <FormField :label="$t('common.client')" :error="form.errors.client_id">
          <select v-model="form.client_id" class="input">
            <option :value="null">{{ $t('common.none') }}</option>
            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </FormField>
        <FormField :label="$t('finance.frequency')" :error="form.errors.frequency" required>
          <select v-model="form.frequency" class="input" required>
            <option value="weekly">{{ $t('finance.weekly') }}</option>
            <option value="monthly">{{ $t('finance.monthly') }}</option>
            <option value="quarterly">{{ $t('finance.quarterly') }}</option>
            <option value="yearly">{{ $t('finance.yearly') }}</option>
          </select>
        </FormField>
        <FormField :label="$t('finance.every_how_many_periods')" :error="form.errors.interval" required>
          <input v-model.number="form.interval" type="number" min="1" max="12" class="input" required />
        </FormField>
        <FormField :label="$t('finance.first_or_next_invoice')" :error="form.errors.next_date" required>
          <input v-model="form.next_date" type="date" class="input" required />
        </FormField>
        <FormField :label="$t('finance.end_after')" :error="form.errors.ends_at" hint="Puste = bez końca">
          <input v-model="form.ends_at" type="date" class="input" />
        </FormField>
        <FormField :label="$t('finance.payment_terms_days')" :error="form.errors['template_data.payment_days']">
          <input v-model.number="form.template_data.payment_days" type="number" min="0" max="365" class="input" />
        </FormField>

        <label class="flex items-center gap-2 text-sm text-gray-700 sm:col-span-2">
          <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
          {{ $t('finance.schedule_active') }}
        </label>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('common.items') }}</h2>

        <div v-for="(item, i) in form.template_data.items" :key="i" class="grid gap-2 sm:grid-cols-12 items-start mb-2">
          <input
            v-model="item.description"
            type="text"
            class="input sm:col-span-5"
            :placeholder="$t('common.description')"
          />
          <input
            v-model.number="item.quantity"
            type="number"
            step="0.01"
            min="0.01"
            class="input sm:col-span-2"
            :placeholder="$t('common.quantity')"
          />
          <input
            v-model.number="item.unit_price"
            type="number"
            step="0.01"
            min="0"
            class="input sm:col-span-2"
            :placeholder="$t('common.price')"
          />
          <select v-model.number="item.tax_rate" class="input sm:col-span-2">
            <option v-for="t in taxRates" :key="t.id" :value="Number(t.rate)">{{ t.rate }}%</option>
          </select>
          <button
            type="button"
            class="text-red-400 hover:text-red-600 text-sm sm:col-span-1 pt-2"
            @click="form.template_data.items.splice(i, 1)"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <button type="button" class="text-xs text-indigo-600 hover:text-indigo-800" @click="addItem">
          {{ $t('finance.add_item_2') }}
        </button>

        <p v-if="form.errors['template_data.items']" class="text-xs text-red-600 mt-2">
          {{ form.errors['template_data.items'] }}
        </p>

        <div class="mt-4 pt-4 border-t border-gray-100 text-right">
          <span class="text-sm text-gray-500">{{ $t('common.gross_total') }}</span>
          <span class="text-lg font-bold text-gray-900 ml-2">{{ fmt(total) }}</span>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary" :disabled="form.processing">
          {{ isEdit ? $t('common.save_changes') : $t('common.create_schedule') }}
        </button>
        <Link :href="route('tenant.manager.recurring-invoices.index')" class="btn-ghost">{{
          $t('common.cancel')
        }}</Link>
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
  recurring: { type: Object, default: null },
  clients: { type: Array, default: () => [] },
  taxRates: { type: Array, default: () => [] },
})

const isEdit = computed(() => Boolean(props.recurring))

const blankItem = () => ({ description: '', quantity: 1, unit_price: 0, tax_rate: 23 })

const form = useForm({
  title: props.recurring?.title ?? '',
  client_id: props.recurring?.client_id ?? null,
  frequency: props.recurring?.frequency ?? 'monthly',
  interval: props.recurring?.interval ?? 1,
  next_date: props.recurring?.next_date?.slice(0, 10) ?? '',
  ends_at: props.recurring?.ends_at?.slice(0, 10) ?? '',
  is_active: props.recurring?.is_active ?? true,
  template_data: {
    items: props.recurring?.template_data?.items ?? [blankItem()],
    currency: props.recurring?.template_data?.currency ?? 'PLN',
    payment_days: props.recurring?.template_data?.payment_days ?? 14,
    notes: props.recurring?.template_data?.notes ?? '',
  },
})

const addItem = () => form.template_data.items.push(blankItem())

const total = computed(() =>
  form.template_data.items.reduce((sum, i) => {
    const net = (Number(i.quantity) || 0) * (Number(i.unit_price) || 0)
    return sum + net * (1 + (Number(i.tax_rate) || 0) / 100)
  }, 0),
)

const fmt = (v) =>
  new Intl.NumberFormat('pl-PL', { style: 'currency', currency: form.template_data.currency }).format(v || 0)

const submit = () => {
  if (isEdit.value) {
    form.put(route('tenant.manager.recurring-invoices.update', props.recurring.id))
  } else {
    form.post(route('tenant.manager.recurring-invoices.store'))
  }
}
</script>
