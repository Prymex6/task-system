<template>
  <ManagerLayout :title="discussion.title">
    <div class="max-w-3xl space-y-5">
      <div class="flex items-center gap-3">
        <Link
          :href="route('tenant.manager.projects.discussions.index', project.id)"
          class="text-gray-400 hover:text-gray-600"
        >
          <i class="fa-solid fa-arrow-left"></i>
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ discussion.title }}</h1>
          <p class="text-xs text-gray-400">{{ discussion.creator?.name }} · {{ formatDate(discussion.created_at) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">{{ discussion.body }}</div>
      </div>

      <!-- Comments -->
      <div class="space-y-3">
        <h2 class="text-sm font-semibold text-gray-700">
          {{ $t('common.comments') }}{{ discussion.comments?.length ?? 0 }})
        </h2>

        <div v-for="c in discussion.comments" :key="c.id" class="flex gap-3">
          <div
            class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold flex-shrink-0"
          >
            {{ c.user?.name?.charAt(0) }}
          </div>
          <div class="flex-1 bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-semibold text-gray-900">{{ c.user?.name }}</span>
              <span class="text-xs text-gray-400">{{ formatDate(c.created_at) }}</span>
            </div>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ c.body }}</p>
          </div>
        </div>

        <!-- Add comment -->
        <form @submit.prevent="addComment" class="flex gap-3">
          <div
            class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold flex-shrink-0 mt-1"
          >
            <i class="fa-solid fa-user text-xs"></i>
          </div>
          <div class="flex-1">
            <textarea
              v-model="commentBody"
              rows="3"
              class="input w-full text-sm mb-2"
              :placeholder="$t('common.write_a_comment')"
              required
            ></textarea>
            <div class="flex justify-end">
              <button type="submit" class="btn-primary text-sm">{{ $t('projects.add_comment') }}</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  project: Object,
  discussion: Object,
})

const commentBody = ref('')

const addComment = () => {
  router.post(
    route('tenant.manager.projects.discussions.comments.store', [props.project.id, props.discussion.id]),
    { body: commentBody.value },
    {
      onSuccess: () => {
        commentBody.value = ''
      },
    },
  )
}

const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('pl-PL', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'
</script>
