<template>
  <ManagerLayout :title="$t('settings.e_mail_templates')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('settings.e_mail_templates') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.reword_the_e_mails_the_system') }}</p>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-5">
        <!-- Template list -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="p-3 border-b border-gray-100 bg-gray-50">
            <span class="text-xs font-semibold text-gray-500">{{ $t('common.templates') }}</span>
          </div>
          <div class="divide-y divide-gray-100">
            <button
              v-for="t in templates"
              :key="t.id"
              @click="selected = t"
              class="w-full text-left px-4 py-3 hover:bg-gray-50 transition-colors"
              :class="selected?.id === t.id ? 'bg-indigo-50 border-l-2 border-indigo-500' : ''"
            >
              <p class="text-sm font-medium text-gray-900">{{ t.name }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ t.event }}</p>
            </button>
          </div>
        </div>

        <!-- Editor -->
        <div class="col-span-2 bg-white rounded-xl border border-gray-200">
          <div v-if="selected">
            <div class="p-4 border-b border-gray-200">
              <h2 class="font-semibold text-gray-900">{{ selected.name }}</h2>
              <p class="text-xs text-gray-400 mt-0.5">{{ $t('settings.event') }} {{ selected.event }}</p>
            </div>
            <form @submit.prevent="save" class="p-5 space-y-4">
              <div>
                <label class="label">{{ $t('common.subject') }}</label>
                <input v-model="form.subject" class="input" required />
              </div>
              <div>
                <label class="label">{{ $t('settings.body_html') }}</label>
                <div class="text-xs text-gray-400 mb-1.5">
                  {{ $t('settings.available_placeholders') }} {{ selected.variables }}
                </div>
                <textarea
                  v-model="form.body_html"
                  rows="14"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                ></textarea>
              </div>
              <div class="flex justify-end gap-2">
                <button type="button" @click="reset" class="btn-ghost text-sm">
                  {{ $t('settings.restore_default') }}
                </button>
                <button type="submit" class="btn-primary text-sm">{{ $t('settings.save_template') }}</button>
              </div>
            </form>
          </div>
          <div v-else class="py-20 text-center">
            <i class="fa-regular fa-envelope text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">{{ $t('settings.pick_a_template_from_the_list') }}</p>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({ templates: { type: Array, default: () => [] } })

const selected = ref(null)
const form = reactive({ subject: '', body_html: '' })

watch(selected, (t) => {
  if (t) {
    form.subject = t.subject ?? ''
    form.body_html = t.body_html ?? ''
  }
})

const save = () => {
  router.put(route('tenant.manager.settings.email-templates.update', selected.value.id), form, { preserveScroll: true })
}

const reset = () => {
  router.post(
    route('tenant.manager.settings.email-templates.reset', selected.value.id),
    {},
    {
      onSuccess: () => router.reload(),
    },
  )
}
</script>
