<template>
  <ClientLayout :title="task.title">
    <div class="max-w-3xl space-y-5">
      <!-- Header -->
      <div class="flex items-start justify-between gap-4">
        <div>
          <Link
            :href="route('tenant.client.tasks.index')"
            class="text-sm text-indigo-600 hover:text-indigo-800 mb-2 block"
          >
            <i class="fa-solid fa-arrow-left mr-1"></i> {{ $t('portal.all_tasks') }}
          </Link>
          <h1 class="text-2xl font-bold text-gray-900" :class="task.completed_at ? 'line-through text-gray-400' : ''">
            {{ task.title }}
          </h1>
        </div>
        <span
          v-if="task.status"
          class="flex-shrink-0 text-sm px-3 py-1 rounded-full font-medium"
          :style="{ background: task.status.color + '22', color: task.status.color }"
        >
          {{ task.status.name }}
        </span>
      </div>

      <!-- Meta -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 gap-4 text-sm">
        <div>
          <p class="text-xs text-gray-500">{{ $t('common.project') }}</p>
          <p class="text-gray-900 font-medium">{{ task.project?.name }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('common.priority') }}</p>
          <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="priorityClass(task.priority)">{{
            task.priority
          }}</span>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('common.due') }}</p>
          <p class="font-medium" :class="isOverdue ? 'text-red-500' : 'text-gray-900'">
            {{ formatDate(task.due_date) ?? '—' }}
          </p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('common.assigned_2') }}</p>
          <div class="flex -space-x-1 mt-0.5">
            <div
              v-for="a in (task.assignees ?? []).slice(0, 4)"
              :key="a.id"
              class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold ring-2 ring-white"
              :title="a.name"
            >
              {{ a.name?.charAt(0) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div v-if="task.description" class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('common.description') }}</h2>
        <div class="prose prose-sm max-w-none text-gray-700" v-html="task.description"></div>
      </div>

      <!-- Checklists -->
      <div v-if="task.checklists?.length" class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('common.checklist') }}</h2>
        <div v-for="list in task.checklists" :key="list.id" class="mb-4">
          <p class="text-xs font-semibold text-gray-600 mb-2">{{ list.title }}</p>
          <div class="space-y-1.5">
            <div v-for="item in list.items" :key="item.id" class="flex items-center gap-2">
              <i
                :class="
                  item.is_completed ? 'fa-solid fa-check-square text-green-500' : 'fa-regular fa-square text-gray-300'
                "
              ></i>
              <span class="text-sm" :class="item.is_completed ? 'line-through text-gray-400' : 'text-gray-700'">
                {{ item.content }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Comments -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">
          {{ $t('common.comments') }}{{ publicComments.length }})
        </h2>
        <div class="space-y-4 mb-5">
          <div v-for="comment in publicComments" :key="comment.id" class="flex gap-3">
            <div
              class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold flex-shrink-0"
            >
              {{ comment.creator?.name?.charAt(0) }}
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-sm font-semibold text-gray-900">{{ comment.creator?.name }}</span>
                <span class="text-xs text-gray-400">{{ formatDate(comment.created_at) }}</span>
              </div>
              <div class="text-sm text-gray-700 prose prose-sm max-w-none" v-html="comment.body"></div>
            </div>
          </div>
        </div>

        <!-- Add comment form -->
        <form @submit.prevent="addComment" class="border-t border-gray-100 pt-4">
          <textarea
            v-model="commentBody"
            rows="3"
            class="input w-full text-sm mb-2"
            :placeholder="$t('common.write_a_comment')"
            required
          ></textarea>
          <div class="flex justify-end">
            <button type="submit" class="btn-primary text-sm">
              <i class="fa-solid fa-paper-plane mr-1"></i> {{ $t('common.send') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

const props = defineProps({ task: Object })

const commentBody = ref('')
const publicComments = computed(() => (props.task.comments ?? []).filter((c) => !c.is_internal))
const isOverdue = computed(
  () => props.task.due_date && !props.task.completed_at && new Date(props.task.due_date) < new Date(),
)

const addComment = () => {
  router.post(
    route('tenant.client.tasks.comment', props.task.id),
    { body: commentBody.value },
    {
      onSuccess: () => {
        commentBody.value = ''
      },
    },
  )
}

const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : null)
const priorityClass = (p) =>
  ({
    urgent: 'bg-red-100 text-red-700',
    high: 'bg-orange-100 text-orange-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-gray-100 text-gray-600',
  })[p] ?? 'bg-gray-100 text-gray-600'
</script>
