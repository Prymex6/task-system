<template>
  <ManagerLayout :title="isEdit ? $t('common.edit_estimate') : $t('finance.new_estimate')">
    <div class="max-w-3xl mx-auto space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.estimates.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <h1 class="page-title">{{ isEdit ? $t('common.edit_estimate') : $t('finance.new_estimate') }}</h1>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="card p-6 space-y-4">
          <h3 class="section-title">{{ $t('common.basics') }}</h3>
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="label">{{ $t('finance.estimate_title') }}</label>
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
                <option value="">{{ $t('finance.optional') }}</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('common.issue_date') }}</label>
              <input v-model="form.issue_date" type="date" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('common.valid_until') }}</label>
              <input v-model="form.valid_until" type="date" class="input" required />
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
              <label class="label">{{ $t('finance.discount') }}</label>
              <input v-model="form.discount" type="number" min="0" max="100" step="0.1" class="input" placeholder="0" />
            </div>
          </div>
        </div>

        <!-- Items -->
        <div class="card p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="section-title mb-0">{{ $t('common.items') }}</h3>
            <button type="button" @click="addItem" class="btn-ghost btn-sm">
              <i class="fa-solid fa-plus"></i> {{ $t('common.add') }}
            </button>
          </div>
          <div class="space-y-3">
            <div v-for="(item, idx) in form.items" :key="idx" class="grid grid-cols-12 gap-2 items-end">
              <div class="col-span-5">
                <label v-if="idx === 0" class="label">{{ $t('common.description') }}</label>
                <input v-model="item.description" type="text" class="input" required />
              </div>
              <div class="col-span-2">
                <label v-if="idx === 0" class="label">{{ $t('common.quantity') }}</label>
                <input
                  v-model="item.quantity"
                  type="number"
                  step="0.01"
                  min="0.01"
                  class="input"
                  required
                  @input="calcItem(item)"
                />
              </div>
              <div class="col-span-2">
                <label v-if="idx === 0" class="label">{{ $t('common.net_price') }}</label>
                <input
                  v-model="item.unit_price"
                  type="number"
                  step="0.01"
                  min="0"
                  class="input"
                  required
                  @input="calcItem(item)"
                />
              </div>
              <div class="col-span-1">
                <label v-if="idx === 0" class="label">VAT%</label>
                <select v-model="item.tax_rate" class="select" @change="calcItem(item)">
                  <option :value="0">0%</option>
                  <option :value="5">5%</option>
                  <option :value="8">8%</option>
                  <option :value="23">23%</option>
                </select>
              </div>
              <div class="col-span-1 text-right">
                <label v-if="idx === 0" class="label">{{ $t('finance.gross') }}</label>
                <div class="text-sm font-semibold text-gray-800 pb-2.5">{{ formatMoney(item._total) }}</div>
              </div>
              <div class="col-span-1">
                <button
                  v-if="form.items.length > 1"
                  type="button"
                  @click="removeItem(idx)"
                  class="btn-ghost btn-sm text-red-500"
                >
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="flex justify-end mt-4 pt-4 border-t border-gray-100">
            <div class="w-52 space-y-1.5 text-sm">
              <div class="flex justify-between text-gray-600">
                <span>{{ $t('common.net') }}</span
                ><span>{{ formatMoney(totals.subtotal) }}</span>
              </div>
              <div class="flex justify-between text-gray-600">
                <span>VAT:</span><span>{{ formatMoney(totals.tax) }}</span>
              </div>
              <div class="flex justify-between font-bold text-base border-t border-gray-200 pt-1.5">
                <span>{{ $t('common.total') }}</span
                ><span>{{ formatMoney(totals.total) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card p-6">
          <label class="label">{{ $t('finance.notes') }}</label>
          <textarea v-model="form.notes" class="textarea" rows="3"></textarea>
        </div>

        <div class="flex justify-end gap-3">
          <Link :href="route('tenant.manager.estimates.index')" class="btn-secondary">{{ $t('common.cancel') }}</Link>
          <button type="submit" :disabled="form.processing" class="btn-primary">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
            {{ isEdit ? $t('common.save_changes') : $t('common.create_estimate') }}
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
  estimate: { type: Object, default: null },
  clients: { type: Array, default: () => [] },
  projects: { type: Array, default: () => [] },
})

const isEdit = computed(() => !!props.estimate)

const makeItem = (item = {}) => ({
  description: item.description ?? '',
  quantity: item.quantity ?? 1,
  unit_price: item.unit_price ?? 0,
  tax_rate: item.tax_rate ?? 23,
  _total: item.total ?? 0,
})

const form = useForm({
  title: props.estimate?.title ?? '',
  client_id: props.estimate?.client_id ?? '',
  project_id: props.estimate?.project_id ?? '',
  issue_date: props.estimate?.issue_date ?? new Date().toISOString().slice(0, 10),
  valid_until: props.estimate?.valid_until ?? '',
  currency: props.estimate?.currency ?? 'PLN',
  discount: props.estimate?.discount ?? 0,
  notes: props.estimate?.notes ?? '',
  items: props.estimate?.items?.map(makeItem) ?? [makeItem()],
})

const calcItem = (item) => {
  const net = parseFloat(item.quantity) * parseFloat(item.unit_price) || 0
  item._total = net * (1 + parseFloat(item.tax_rate) / 100)
}

const addItem = () => form.items.push(makeItem())
const removeItem = (idx) => form.items.splice(idx, 1)

const totals = computed(() => {
  let subtotal = 0,
    tax = 0
  form.items.forEach((item) => {
    const net = parseFloat(item.quantity) * parseFloat(item.unit_price) || 0
    const t = net * (parseFloat(item.tax_rate) / 100)
    subtotal += net
    tax += t
  })
  return { subtotal, tax, total: subtotal + tax }
})

const submit = () => {
  if (isEdit.value) {
    form.put(route('tenant.manager.estimates.update', props.estimate.id))
  } else {
    form.post(route('tenant.manager.estimates.store'))
  }
}

const formatMoney = (v) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v ?? 0)
</script>
