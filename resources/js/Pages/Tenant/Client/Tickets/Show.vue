<template>
  <ClientLayout :title="ticket.subject">
    <div class="space-y-5 max-w-3xl">
      <div class="flex items-center gap-3">
        <Link :href="route('tenant.portal.tickets')" class="text-sm text-indigo-600 hover:text-indigo-700">{{
          $t('portal.tickets_2')
        }}</Link>
        <span class="text-gray-300">/</span>
        <h1 class="text-lg font-bold text-gray-900">{{ ticket.subject }}</h1>
      </div>

      <!-- Status bar -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between">
        <div class="flex gap-4 text-sm">
          <span
            ><span class="text-gray-400">{{ $t('portal.status') }}</span>
            <span class="font-medium" :class="ticketStatusClass(ticket.status)">{{
              ticketStatusLabel(ticket.status)
            }}</span></span
          >
          <span
            ><span class="text-gray-400">{{ $t('portal.priority') }}</span>
            <span class="font-medium">{{ priorityLabel(ticket.priority) }}</span></span
          >
          <span
            ><span class="text-gray-400">{{ $t('portal.department') }}</span> <span>{{ ticket.department }}</span></span
          >
        </div>
        <span class="text-xs text-gray-400">{{ formatDate(ticket.created_at) }}</span>
      </div>

      <!-- Messages -->
      <div class="space-y-4">
        <div
          v-for="reply in ticket.replies"
          :key="reply.id"
          class="bg-white rounded-xl border p-5"
          :class="
            reply.is_internal
              ? 'border-amber-200 bg-amber-50'
              : reply.is_from_client
                ? 'border-indigo-200'
                : 'border-gray-200'
          "
        >
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <div
                class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold"
              >
                {{ (reply.user?.name ?? 'K').charAt(0).toUpperCase() }}
              </div>
              <div>
                <div class="text-sm font-medium text-gray-800">{{ reply.user?.name ?? 'Klient' }}</div>
                <div class="text-xs text-gray-400">{{ formatDate(reply.created_at) }}</div>
              </div>
            </div>
            <span v-if="!reply.is_from_client" class="badge badge-indigo text-xs">{{ $t('common.support') }}</span>
          </div>
          <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ reply.message }}</div>
          <div v-if="reply.attachments?.length" class="mt-3 flex flex-wrap gap-2">
            <a
              v-for="att in reply.attachments"
              :key="att.id"
              :href="att.url"
              target="_blank"
              class="flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-700 bg-indigo-50 px-2 py-1 rounded"
            >
              <i class="fa-solid fa-paperclip"></i> {{ att.name }}
            </a>
          </div>
        </div>
      </div>

      <!-- Reply form (only if not closed) -->
      <div v-if="ticket.status !== 'closed'" class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-900 mb-3">{{ $t('portal.reply') }}</h3>
        <form @submit.prevent="reply">
          <textarea
            v-model="replyForm.message"
            class="textarea mb-3"
            rows="4"
            :placeholder="$t('platform.write_a_reply')"
            required
          ></textarea>
          <div class="mb-3">
            <input
              type="file"
              multiple
              @change="(e) => (replyForm.attachments = Array.from(e.target.files))"
              class="input"
            />
          </div>
          <div class="flex justify-end gap-3">
            <button type="submit" :disabled="replyForm.processing" class="btn-primary">
              <i v-if="replyForm.processing" class="fa-solid fa-spinner fa-spin"></i>
              {{ $t('platform.send_reply') }}
            </button>
          </div>
        </form>
      </div>

      <div
        v-if="ticket.status === 'closed'"
        class="bg-gray-50 rounded-xl border border-gray-200 p-4 text-center text-sm text-gray-500"
      >
        {{ $t('portal.this_ticket_is_closed') }}
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link, useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

const props = defineProps({ ticket: Object })

const replyForm = useForm({ message: '', attachments: [] })

const reply = () => {
  replyForm.post(route('tenant.portal.tickets.reply', props.ticket.id), {
    forceFormData: true,
    onSuccess: () => replyForm.reset(),
  })
}

const priorityLabel = (p) => ({ low: 'Niski', medium: t('common.medium'), high: 'Wysoki', urgent: 'Pilny' })[p] ?? p
const ticketStatusLabel = (s) =>
  ({ open: 'Otwarty', in_progress: 'W trakcie', resolved: t('common.resolved_2'), closed: t('common.closed_2') })[s] ??
  s
const ticketStatusClass = (s) =>
  ({ open: 'text-blue-600', in_progress: 'text-indigo-600', resolved: 'text-emerald-600', closed: 'text-gray-500' })[
    s
  ] ?? ''
const formatDate = (d) => (d ? new Date(d).toLocaleString('pl-PL') : '—')
</script>
