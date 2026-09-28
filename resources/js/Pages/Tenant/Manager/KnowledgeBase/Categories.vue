<template>
  <ManagerLayout :title="$t('kb.kb_categories')">
    <div class="max-w-2xl space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('kb.knowledge_base_categories') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('kb.group_articles_into_categories') }}</p>
        </div>
        <div class="flex items-center gap-2">
          <Link :href="route('tenant.manager.kb.index')" class="btn-ghost text-sm">{{ $t('kb.articles') }}</Link>
          <button @click="showCreate = true" class="btn-primary text-sm">
            <i class="fa-solid fa-plus mr-1"></i> {{ $t('kb.new_category') }}
          </button>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.category') }}</th>
              <th class="th text-center">{{ $t('kb.articles') }}</th>
              <th class="th text-center">{{ $t('kb.public') }}</th>
              <th class="th text-right">{{ $t('common.order') }}</th>
              <th class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50">
              <td class="td">
                <div class="flex items-center gap-2">
                  <i v-if="cat.icon" :class="cat.icon + ' text-indigo-500 w-4'"></i>
                  <span class="text-sm font-medium text-gray-900">{{ cat.name }}</span>
                </div>
              </td>
              <td class="td text-center text-sm text-gray-600">{{ cat.articles_count ?? 0 }}</td>
              <td class="td text-center">
                <i
                  :class="cat.is_public ? 'fa-solid fa-eye text-green-500' : 'fa-solid fa-eye-slash text-gray-300'"
                ></i>
              </td>
              <td class="td text-right text-sm text-gray-400">{{ cat.order }}</td>
              <td class="td text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="edit(cat)" class="text-xs text-indigo-600 hover:text-indigo-800">
                    {{ $t('common.edit') }}
                  </button>
                  <button @click="del(cat)" class="text-xs text-red-400 hover:text-red-600">
                    {{ $t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!categories.length" class="py-16 text-center">
          <i class="fa-solid fa-folder text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('kb.no_categories_yet_add_the_first') }}</p>
        </div>
      </div>

      <!-- Create/Edit modal -->
      <div
        v-if="showCreate || editing"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-xl shadow-xl w-80 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">
            {{ editing ? $t('common.edit_category') : 'Nowa kategoria' }}
          </h3>
          <form @submit.prevent="save" class="space-y-3">
            <div>
              <label class="label">{{ $t('common.name') }}</label>
              <input v-model="form.name" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('kb.icon_font_awesome') }}</label>
              <input v-model="form.icon" class="input" placeholder="fa-solid fa-book" />
            </div>
            <div>
              <label class="label">{{ $t('common.order') }}</label>
              <input v-model="form.order" type="number" class="input" />
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" v-model="form.is_public" />
              {{ $t('kb.visible_to_clients') }}
            </label>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="closeModal" class="btn-ghost text-sm">{{ $t('common.cancel') }}</button>
              <button type="submit" class="btn-primary text-sm">{{ $t('common.save') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({ categories: { type: Array, default: () => [] } })

const showCreate = ref(false)
const editing = ref(null)
const form = reactive({ name: '', icon: '', order: 0, is_public: true })

const edit = (cat) => {
  editing.value = cat
  Object.assign(form, { name: cat.name, icon: cat.icon ?? '', order: cat.order ?? 0, is_public: cat.is_public ?? true })
}

const closeModal = () => {
  showCreate.value = false
  editing.value = null
  Object.assign(form, { name: '', icon: '', order: 0, is_public: true })
}

const save = () => {
  if (editing.value) {
    router.put(route('tenant.manager.kb-categories.update', editing.value.id), form, { onSuccess: closeModal })
  } else {
    router.post(route('tenant.manager.kb-categories.store'), form, { onSuccess: closeModal })
  }
}

const del = (cat) => {
  if (confirm(t('kb.delete_this_category_its_articles_will'))) {
    router.delete(route('tenant.manager.kb-categories.destroy', cat.id))
  }
}
</script>
