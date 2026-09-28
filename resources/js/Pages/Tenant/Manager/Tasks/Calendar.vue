<template>
  <ManagerLayout :title="$t('tasks.task_calendar')">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('tasks.task_calendar') }}</h1>
        </div>
        <div class="flex items-center gap-3">
          <button @click="prevMonth" class="btn-ghost"><i class="fa-solid fa-chevron-left"></i></button>
          <span class="font-semibold text-gray-700 w-44 text-center">{{ monthLabel }}</span>
          <button @click="nextMonth" class="btn-ghost"><i class="fa-solid fa-chevron-right"></i></button>
          <button @click="goToday" class="btn-ghost text-sm">{{ $t('tasks.today') }}</button>
          <Link :href="route('tenant.manager.tasks.create')" class="btn-primary text-sm">
            <i class="fa-solid fa-plus mr-1"></i> {{ $t('common.new_task') }}
          </Link>
        </div>
      </div>

      <!-- Calendar grid -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <!-- Day names -->
        <div class="grid grid-cols-7 border-b border-gray-200">
          <div v-for="d in dayNames" :key="d" class="py-2 text-center text-xs font-semibold text-gray-500">
            {{ d }}
          </div>
        </div>

        <!-- Weeks -->
        <div class="grid grid-cols-7">
          <div
            v-for="day in calendarDays"
            :key="day.date"
            class="border-b border-r border-gray-100 min-h-[110px] p-1.5"
            :class="{ 'bg-gray-50': !day.inMonth, 'bg-indigo-50/40': day.isToday }"
          >
            <div class="flex items-center justify-between mb-1">
              <span
                class="text-xs font-semibold"
                :class="
                  day.isToday
                    ? 'bg-indigo-600 text-white w-5 h-5 rounded-full flex items-center justify-center'
                    : day.inMonth
                      ? 'text-gray-700'
                      : 'text-gray-300'
                "
              >
                {{ day.dayNum }}
              </span>
            </div>

            <div class="space-y-0.5">
              <Link
                v-for="task in day.tasks"
                :key="task.id"
                :href="route('tenant.manager.tasks.show', task.id)"
                class="block text-xs truncate px-1.5 py-0.5 rounded cursor-pointer hover:opacity-80 transition-opacity"
                :style="{ background: task.status?.color + '33', color: task.status?.color ?? '#4f46e5' }"
                :title="task.title"
              >
                <i :class="task.is_completed ? 'fa-solid fa-check mr-0.5' : 'fa-regular fa-circle mr-0.5'"></i>
                {{ task.title }}
              </Link>

              <span
                v-if="day.more > 0"
                class="text-xs text-indigo-600 cursor-pointer hover:underline px-1"
                @click="selectedDay = day"
              >
                +{{ day.more }} {{ $t('projects.more') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Day detail modal -->
      <div
        v-if="selectedDay"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="selectedDay = null"
      >
        <div class="bg-white rounded-xl shadow-xl w-80 p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">{{ selectedDay.fullDate }}</h3>
            <button @click="selectedDay = null" class="text-gray-400 hover:text-gray-600">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <div class="space-y-2">
            <Link
              v-for="task in selectedDay.allTasks"
              :key="task.id"
              :href="route('tenant.manager.tasks.show', task.id)"
              class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 text-sm"
            >
              <i
                :class="
                  task.is_completed ? 'fa-solid fa-circle-check text-green-500' : 'fa-regular fa-circle text-gray-300'
                "
              ></i>
              <span :class="task.is_completed ? 'line-through text-gray-400' : 'text-gray-800'">{{ task.title }}</span>
            </Link>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  tasks: Array,
})

const today = new Date()
const current = ref({ year: today.getFullYear(), month: today.getMonth() })
const selectedDay = ref(null)
const maxVisible = 3

const dayNames = ['Pn', 'Wt', t('common.wed'), 'Cz', 'Pt', 'So', 'Nd']
const monthNames = [
  t('common.january'),
  'Luty',
  'Marzec',
  t('common.april'),
  'Maj',
  'Czerwiec',
  'Lipiec',
  t('common.august'),
  t('common.september'),
  t('common.october'),
  'Listopad',
  t('common.december'),
]

const monthLabel = computed(() => `${monthNames[current.value.month]} ${current.value.year}`)

const tasksByDate = computed(() => {
  const map = {}
  for (const task of props.tasks ?? []) {
    if (!task.due_date) continue
    const key = task.due_date.slice(0, 10)
    if (!map[key]) map[key] = []
    map[key].push(task)
  }
  return map
})

const calendarDays = computed(() => {
  const { year, month } = current.value
  const first = new Date(year, month, 1)
  const last = new Date(year, month + 1, 0)

  let startDow = first.getDay() - 1
  if (startDow < 0) startDow = 6

  const days = []
  for (let i = 0; i < startDow; i++) {
    const d = new Date(year, month, -startDow + i + 1)
    const key = d.toISOString().slice(0, 10)
    days.push({
      date: key,
      dayNum: d.getDate(),
      inMonth: false,
      isToday: false,
      tasks: [],
      allTasks: [],
      more: 0,
      fullDate: '',
    })
  }

  for (let d = 1; d <= last.getDate(); d++) {
    const date = new Date(year, month, d)
    const key = date.toISOString().slice(0, 10)
    const allTasks = tasksByDate.value[key] ?? []
    const isToday = date.toDateString() === today.toDateString()
    days.push({
      date: key,
      dayNum: d,
      inMonth: true,
      isToday,
      allTasks,
      tasks: allTasks.slice(0, maxVisible),
      more: Math.max(0, allTasks.length - maxVisible),
      fullDate: date.toLocaleDateString('pl-PL', { weekday: 'long', day: 'numeric', month: 'long' }),
    })
  }

  const remaining = 42 - days.length
  for (let i = 1; i <= remaining; i++) {
    const d = new Date(year, month + 1, i)
    const key = d.toISOString().slice(0, 10)
    days.push({ date: key, dayNum: i, inMonth: false, isToday: false, tasks: [], allTasks: [], more: 0, fullDate: '' })
  }

  return days
})

const prevMonth = () => {
  const { year, month } = current.value
  current.value = month === 0 ? { year: year - 1, month: 11 } : { year, month: month - 1 }
  fetchTasks()
}
const nextMonth = () => {
  const { year, month } = current.value
  current.value = month === 11 ? { year: year + 1, month: 0 } : { year, month: month + 1 }
  fetchTasks()
}
const goToday = () => {
  current.value = { year: today.getFullYear(), month: today.getMonth() }
  fetchTasks()
}

const fetchTasks = () => {
  const { year, month } = current.value
  router.get(route('tenant.manager.tasks.calendar'), { year, month: month + 1 }, { preserveState: true, replace: true })
}
</script>
