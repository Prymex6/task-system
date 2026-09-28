<template>
  <ManagerLayout :title="$t('manager.messages')">
    <div class="space-y-5">
      <div class="page-header">
        <h1 class="page-title">{{ $t('manager.internal_messages') }}</h1>
        <button @click="showNew = true" class="btn-primary">
          <i class="fa-solid fa-pen-to-square"></i> {{ $t('manager.new_message') }}
        </button>
      </div>

      <div class="grid grid-cols-3 gap-0 rounded-xl border border-gray-200 bg-white overflow-hidden min-h-[60vh]">
        <!-- Conversation list -->
        <div class="border-r border-gray-200 overflow-y-auto">
          <div class="p-3 border-b border-gray-100">
            <input v-model="search" type="text" class="input input-sm" :placeholder="$t('common.search')" />
          </div>
          <div v-if="!conversations.length" class="p-6 text-center text-sm text-gray-400">
            {{ $t('manager.no_messages') }}
          </div>
          <div
            v-for="conv in filteredConversations"
            :key="conv.id"
            @click="selected = conv"
            class="px-4 py-3 cursor-pointer border-b border-gray-50 hover:bg-gray-50 transition-colors"
            :class="{ 'bg-indigo-50': selected?.id === conv.id }"
          >
            <div class="flex items-center justify-between">
              <div class="font-medium text-sm text-gray-800 truncate">{{ conv.with?.name }}</div>
              <div class="text-xs text-gray-400">{{ timeAgo(conv.last_message?.created_at) }}</div>
            </div>
            <div class="text-xs text-gray-500 truncate mt-0.5">{{ conv.last_message?.body }}</div>
            <div v-if="conv.unread > 0" class="mt-1">
              <span class="badge bg-indigo-600 text-white text-xs">{{ conv.unread }}</span>
            </div>
          </div>
        </div>

        <!-- Message area -->
        <div class="col-span-2 flex flex-col">
          <div v-if="!selected" class="flex-1 flex items-center justify-center text-gray-400">
            <div class="text-center">
              <i class="fa-solid fa-comments text-4xl mb-3"></i>
              <div>{{ $t('manager.pick_a_conversation') }}</div>
            </div>
          </div>
          <template v-else>
            <div class="px-5 py-3 border-b border-gray-200 font-semibold text-gray-900">
              {{ selected.with?.name }}
            </div>
            <div class="flex-1 overflow-y-auto p-5 space-y-3">
              <div
                v-for="msg in selected.messages"
                :key="msg.id"
                class="flex"
                :class="msg.is_mine ? 'justify-end' : 'justify-start'"
              >
                <div
                  class="max-w-xs px-4 py-2.5 rounded-2xl text-sm"
                  :class="
                    msg.is_mine ? 'bg-indigo-600 text-white rounded-br-sm' : 'bg-gray-100 text-gray-800 rounded-bl-sm'
                  "
                >
                  {{ msg.body }}
                  <div class="text-xs mt-1 opacity-60">{{ timeAgo(msg.created_at) }}</div>
                </div>
              </div>
            </div>
            <div class="p-4 border-t border-gray-200">
              <form @submit.prevent="sendMessage" class="flex gap-2">
                <input
                  v-model="newMessage"
                  type="text"
                  class="input flex-1"
                  :placeholder="$t('manager.write_a_message')"
                  required
                />
                <button type="submit" class="btn-primary btn-sm">
                  <i class="fa-solid fa-paper-plane"></i>
                </button>
              </form>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- New message modal -->
    <div v-if="showNew" class="modal-backdrop" @click.self="showNew = false">
      <div class="modal-sm">
        <div class="modal-header">
          <h3 class="modal-title">{{ $t('manager.new_message') }}</h3>
          <button @click="showNew = false" class="btn-ghost btn-sm"><i class="fa-solid fa-times"></i></button>
        </div>
        <form @submit.prevent="startConversation">
          <div class="modal-body space-y-4">
            <div>
              <label class="label">{{ $t('hr.to') }}</label>
              <select v-model="newForm.recipient_id" class="select" required>
                <option value="">{{ $t('manager.choose_a_person') }}</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('platform.message') }}</label>
              <textarea v-model="newForm.body" class="textarea" rows="4" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="showNew = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="newForm.processing" class="btn-primary">{{ $t('common.send') }}</button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  conversations: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
})

const selected = ref(null)
const search = ref('')
const newMessage = ref('')
const showNew = ref(false)

const filteredConversations = computed(() =>
  props.conversations.filter((c) => !search.value || c.with?.name?.toLowerCase().includes(search.value.toLowerCase())),
)

const sendMessage = () => {
  if (!newMessage.value.trim() || !selected.value) return
  useForm({ body: newMessage.value }).post(route('tenant.manager.messages.send', selected.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      newMessage.value = ''
    },
  })
}

const newForm = useForm({ recipient_id: '', body: '' })
const startConversation = () => {
  newForm.post(route('tenant.manager.messages.store'), {
    onSuccess: () => {
      showNew.value = false
      newForm.reset()
    },
  })
}

const timeAgo = (date) => {
  if (!date) return ''
  const diff = Date.now() - new Date(date).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'teraz'
  if (mins < 60) return `${mins} min`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs}h`
  return new Date(date).toLocaleDateString('pl-PL')
}
</script>
