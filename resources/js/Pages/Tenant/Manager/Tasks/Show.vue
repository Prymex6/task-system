<template>
  <ManagerLayout :title="task.title">
    <div class="grid grid-cols-3 gap-5">
      <!-- Main column -->
      <div class="col-span-2 space-y-5">
        <!-- Header -->
        <div class="flex items-start justify-between">
          <div class="flex items-start gap-3 flex-1">
            <button
              @click="toggleComplete"
              class="mt-1 text-xl flex-shrink-0"
              :class="task.is_completed ? 'text-green-500 hover:text-green-600' : 'text-gray-300 hover:text-green-400'"
            >
              <i :class="task.is_completed ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'"></i>
            </button>
            <div class="flex-1">
              <h1
                :class="task.is_completed ? 'line-through text-gray-400' : 'text-gray-900'"
                class="text-2xl font-bold leading-tight"
              >
                {{ task.title }}
              </h1>
              <div class="flex items-center gap-3 mt-2 flex-wrap">
                <span
                  v-if="task.status"
                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                  :style="{ background: task.status.color + '22', color: task.status.color }"
                >
                  {{ task.status.name }}
                </span>
                <span :class="priorityClass(task.priority)" class="text-xs px-2 py-0.5 rounded-full font-medium">
                  {{ task.priority }}
                </span>
                <span class="text-xs text-gray-400">
                  <i class="fa-solid fa-folder-open mr-1"></i>
                  <Link :href="route('tenant.manager.projects.show', task.project.id)" class="hover:text-indigo-600">{{
                    task.project.name
                  }}</Link>
                </span>
              </div>
            </div>
          </div>
          <div class="flex gap-2 flex-shrink-0">
            <Link :href="route('tenant.manager.tasks.edit', task.id)" class="btn-secondary text-sm">
              <i class="fa-solid fa-pen mr-1"></i> {{ $t('common.edit') }}
            </Link>
          </div>
        </div>

        <!-- Description -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('common.description') }}</h3>
          <div
            v-if="task.description"
            class="text-sm text-gray-700 whitespace-pre-line leading-relaxed"
            v-html="task.description"
          ></div>
          <p v-else class="text-sm text-gray-400 italic">{{ $t('projects.no_description') }}</p>
        </div>

        <!-- Checklists -->
        <div v-if="task.checklists?.length" class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
          <h3 class="text-sm font-semibold text-gray-700">Checklists</h3>
          <div v-for="checklist in task.checklists" :key="checklist.id" class="space-y-2">
            <p class="text-sm font-medium text-gray-700">{{ checklist.name }}</p>
            <div class="space-y-1.5">
              <label
                v-for="item in checklist.items"
                :key="item.id"
                class="flex items-center gap-2 cursor-pointer group"
              >
                <input
                  type="checkbox"
                  :checked="item.is_completed"
                  @change="toggleChecklistItem(item)"
                  class="rounded border-gray-300 text-indigo-600"
                />
                <span :class="item.is_completed ? 'line-through text-gray-400' : 'text-gray-700'" class="text-sm">{{
                  item.content
                }}</span>
              </label>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full mt-2">
              <div
                class="h-1.5 bg-indigo-500 rounded-full"
                :style="{ width: checklistProgress(checklist) + '%' }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Subtasks -->
        <div v-if="task.subtasks?.length" class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">
            {{ $t('tasks.subtasks') }}{{ task.subtasks.length }})
          </h3>
          <div class="space-y-2">
            <div
              v-for="sub in task.subtasks"
              :key="sub.id"
              class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50"
            >
              <i
                :class="
                  sub.is_completed ? 'fa-solid fa-circle-check text-green-500' : 'fa-regular fa-circle text-gray-300'
                "
              ></i>
              <Link
                :href="route('tenant.manager.tasks.show', sub.id)"
                :class="sub.is_completed ? 'line-through text-gray-400' : 'text-gray-800'"
                class="text-sm flex-1 hover:text-indigo-600"
                >{{ sub.title }}</Link
              >
              <span :class="priorityClass(sub.priority)" class="text-xs px-1.5 py-0.5 rounded-full">{{
                sub.priority
              }}</span>
            </div>
          </div>
        </div>

        <!-- Time entries -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">
              {{ $t('tasks.time_logged') }}
              <span class="text-gray-400 font-normal ml-1">({{ totalHours }}{{ $t('tasks.h_in_total') }}</span>
            </h3>
            <button @click="showTimeForm = !showTimeForm" class="text-sm text-indigo-600 hover:underline">
              <i class="fa-solid fa-plus"></i> {{ $t('common.add') }}
            </button>
          </div>

          <!-- Add time form -->
          <form v-if="showTimeForm" @submit.prevent="logTime" class="bg-gray-50 rounded-lg p-3 mb-3 space-y-3">
            <div class="grid grid-cols-3 gap-2">
              <div>
                <label class="label text-xs">{{ $t('common.date') }}</label>
                <input v-model="timeForm.date" type="date" class="input-sm" />
              </div>
              <div>
                <label class="label text-xs">{{ $t('common.hours') }}</label>
                <input
                  v-model="timeForm.hours"
                  type="number"
                  step="0.25"
                  min="0.1"
                  max="24"
                  class="input-sm"
                  placeholder="1.5"
                />
              </div>
              <div class="flex items-end gap-2">
                <button type="submit" :disabled="timeForm.processing" class="btn-primary btn-sm">
                  {{ $t('common.save') }}
                </button>
                <button type="button" @click="showTimeForm = false" class="btn-secondary btn-sm">
                  {{ $t('common.cancel') }}
                </button>
              </div>
            </div>
            <input
              v-model="timeForm.description"
              class="input-sm w-full"
              :placeholder="$t('tasks.description_optional')"
            />
          </form>

          <div v-if="task.time_entries?.length" class="space-y-2">
            <div
              v-for="entry in task.time_entries"
              :key="entry.id"
              class="flex items-center gap-3 text-sm py-1.5 border-b border-gray-50 last:border-0"
            >
              <div
                class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold flex-shrink-0"
              >
                {{ entry.user?.name?.charAt(0)?.toUpperCase() }}
              </div>
              <span class="text-gray-600 flex-1">{{ entry.user?.name }} · {{ entry.date }}</span>
              <span class="font-medium text-gray-900">{{ entry.hours }}h</span>
              <span class="text-gray-400 text-xs truncate max-w-[150px]">{{ entry.description }}</span>
            </div>
          </div>
          <p v-else-if="!showTimeForm" class="text-sm text-gray-400">{{ $t('tasks.no_time_logged') }}</p>
        </div>

        <!-- Comments -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-4">
            {{ $t('common.comments') }}{{ task.comments?.length ?? 0 }})
          </h3>

          <div class="space-y-4 mb-4">
            <div v-for="comment in task.comments" :key="comment.id" class="flex gap-3">
              <div
                class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm flex-shrink-0"
              >
                {{ comment.creator?.name?.charAt(0)?.toUpperCase() }}
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-sm font-medium text-gray-900">{{ comment.creator?.name }}</span>
                  <span class="text-xs text-gray-400">{{ comment.created_at }}</span>
                  <span
                    v-if="comment.is_internal"
                    class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded"
                    >{{ $t('tasks.internal') }}</span
                  >
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700 whitespace-pre-line">
                  {{ comment.body }}
                </div>
              </div>
            </div>
          </div>

          <!-- Add comment -->
          <form @submit.prevent="submitComment" class="flex gap-3">
            <div
              class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm flex-shrink-0"
            >
              {{ $page.props.auth?.user?.name?.charAt(0)?.toUpperCase() }}
            </div>
            <div class="flex-1">
              <textarea
                v-model="commentForm.body"
                rows="3"
                class="input w-full text-sm"
                :placeholder="$t('common.write_a_comment')"
              ></textarea>
              <div class="flex justify-between mt-2">
                <label class="flex items-center gap-1 text-xs text-gray-500 cursor-pointer">
                  <input type="checkbox" v-model="commentForm.is_internal" class="rounded border-gray-300" />
                  {{ $t('tasks.internal_comment') }}
                </label>
                <button
                  type="submit"
                  :disabled="commentForm.processing || !commentForm.body"
                  class="btn-primary btn-sm"
                >
                  {{ $t('projects.add_comment') }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Right sidebar -->
      <div class="space-y-4">
        <!-- Status -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <h3 class="text-xs font-semibold text-gray-500 uppercase mb-3">{{ $t('common.status') }}</h3>
          <select
            :value="task.status_id"
            @change="quickUpdate('status_id', $event.target.value)"
            class="input w-full text-sm"
          >
            <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>

        <!-- Details -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
          <h3 class="text-xs font-semibold text-gray-500 uppercase">{{ $t('common.details') }}</h3>
          <div class="text-sm space-y-2.5">
            <div>
              <p class="text-gray-400 text-xs mb-1">{{ $t('common.priority') }}</p>
              <span :class="priorityClass(task.priority)" class="text-xs px-2 py-0.5 rounded-full font-medium">
                {{ task.priority }}
              </span>
            </div>
            <div>
              <p class="text-gray-400 text-xs mb-1">{{ $t('common.due') }}</p>
              <p :class="isOverdue ? 'text-red-500' : 'text-gray-700'" class="font-medium text-sm">
                {{ task.due_date ?? '—' }}
              </p>
            </div>
            <div v-if="task.estimated_hours">
              <p class="text-gray-400 text-xs mb-1">{{ $t('tasks.estimate') }}</p>
              <p class="text-gray-700 font-medium">{{ task.estimated_hours }}h</p>
            </div>
            <div v-if="task.story_points">
              <p class="text-gray-400 text-xs mb-1">Story Points</p>
              <p class="text-gray-700 font-medium">{{ task.story_points }}</p>
            </div>
            <div v-if="task.milestone">
              <p class="text-gray-400 text-xs mb-1">{{ $t('tasks.milestone') }}</p>
              <p class="text-gray-700 font-medium flex items-center gap-1">
                <i class="fa-solid fa-flag text-indigo-400"></i>
                {{ task.milestone.name }}
              </p>
            </div>
          </div>
        </div>

        <!-- Assignees -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <h3 class="text-xs font-semibold text-gray-500 uppercase mb-3">{{ $t('tasks.assignees') }}</h3>
          <div class="space-y-2">
            <div v-for="a in task.assignees" :key="a.id" class="flex items-center gap-2">
              <div
                class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold"
              >
                {{ a.name?.charAt(0)?.toUpperCase() }}
              </div>
              <span class="text-sm text-gray-700">{{ a.name }}</span>
            </div>
            <p v-if="!task.assignees?.length" class="text-xs text-gray-400">{{ $t('tasks.unassigned') }}</p>
          </div>
        </div>

        <!-- Labels -->
        <div v-if="task.labels?.length" class="bg-white rounded-xl border border-gray-200 p-4">
          <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ $t('tasks.labels') }}</h3>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="label in task.labels"
              :key="label.id"
              class="text-xs px-2 py-0.5 rounded-full font-medium"
              :style="{ background: label.color + '22', color: label.color }"
            >
              {{ label.name }}
            </span>
          </div>
        </div>

        <!-- Creator -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ $t('tasks.created_by') }}</h3>
          <div class="flex items-center gap-2">
            <div
              class="w-6 h-6 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-xs font-bold"
            >
              {{ task.creator?.name?.charAt(0)?.toUpperCase() }}
            </div>
            <span class="text-sm text-gray-700">{{ task.creator?.name }}</span>
          </div>
          <p class="text-xs text-gray-400 mt-1">{{ task.created_at }}</p>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  task: Object,
  staff: Array,
  statuses: Array,
  labels: Array,
  myRole: String,
})

const $page = usePage()

const showTimeForm = ref(false)

const commentForm = useForm({
  body: '',
  is_internal: false,
})

const timeForm = useForm({
  task_id: props.task.id,
  date: new Date().toISOString().split('T')[0],
  hours: '',
  description: '',
  is_billable: true,
})

const totalHours = computed(() => (props.task.time_entries ?? []).reduce((s, e) => s + Number(e.hours), 0).toFixed(1))

const isOverdue = computed(() => {
  if (!props.task.due_date || props.task.is_completed) return false
  return new Date(props.task.due_date) < new Date()
})

const toggleComplete = () => {
  router.put(
    route('tenant.manager.tasks.update', props.task.id),
    {
      ...props.task,
      is_completed: !props.task.is_completed,
      assignees: props.task.assignees?.map((a) => a.id) ?? [],
      labels: props.task.labels?.map((l) => l.id) ?? [],
    },
    { preserveScroll: true },
  )
}

const quickUpdate = (field, value) => {
  router.put(
    route('tenant.manager.tasks.update', props.task.id),
    {
      ...props.task,
      [field]: value,
      assignees: props.task.assignees?.map((a) => a.id) ?? [],
      labels: props.task.labels?.map((l) => l.id) ?? [],
    },
    { preserveScroll: true },
  )
}

const submitComment = () => {
  commentForm.post(route('tenant.manager.tasks.comments.store', props.task.id), {
    preserveScroll: true,
    onSuccess: () => commentForm.reset('body', 'is_internal'),
  })
}

const logTime = () => {
  timeForm.post(route('tenant.manager.timer.store-entry'), {
    preserveScroll: true,
    onSuccess: () => {
      showTimeForm.value = false
      timeForm.reset('hours', 'description')
    },
  })
}

const toggleChecklistItem = (item) => {
  router.put(
    route('tenant.manager.tasks.checklist-items.update', [props.task.id, item.id]),
    { is_completed: !item.is_completed },
    { preserveScroll: true },
  )
}

const checklistProgress = (checklist) => {
  const items = checklist.items ?? []
  if (!items.length) return 0
  return Math.round((items.filter((i) => i.is_completed).length / items.length) * 100)
}

const priorityClass = (p) =>
  ({
    urgent: 'bg-red-100 text-red-700',
    high: 'bg-orange-100 text-orange-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-gray-100 text-gray-600',
  })[p] ?? 'bg-gray-100 text-gray-600'
</script>
