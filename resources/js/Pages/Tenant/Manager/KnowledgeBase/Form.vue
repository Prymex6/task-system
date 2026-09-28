<template>
  <ManagerLayout :title="isEdit ? $t('common.edit_article') : $t('common.new_article')">
    <div class="max-w-3xl mx-auto space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.kb.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <h1 class="page-title">{{ isEdit ? $t('common.edit_article') : $t('common.new_kb_article') }}</h1>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="card p-6 space-y-4">
          <div>
            <label class="label">{{ $t('common.title') }} <span class="text-red-500">*</span></label>
            <input
              v-model="form.title"
              type="text"
              class="input"
              :class="{ 'input-error': form.errors.title }"
              required
            />
            <p v-if="form.errors.title" class="form-error">{{ form.errors.title }}</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">{{ $t('common.category') }}</label>
              <input
                v-model="form.category"
                type="text"
                class="input"
                :placeholder="$t('kb.e_g_invoicing')"
                list="kb-categories"
              />
              <datalist id="kb-categories">
                <option v-for="cat in categories" :key="cat" :value="cat" />
              </datalist>
            </div>
            <div>
              <label class="label">{{ $t('common.status') }}</label>
              <select v-model="form.status" class="select">
                <option value="draft">{{ $t('common.draft') }}</option>
                <option value="published">{{ $t('kb.published') }}</option>
              </select>
            </div>
          </div>
          <div>
            <label class="label">{{ $t('kb.excerpt') }}</label>
            <textarea
              v-model="form.excerpt"
              class="textarea"
              rows="2"
              :placeholder="$t('kb.a_short_summary_of_the_article')"
            ></textarea>
          </div>
        </div>

        <div class="card p-6">
          <label class="label">{{ $t('kb.article_body') }}</label>
          <textarea
            v-model="form.content"
            class="textarea"
            rows="16"
            :placeholder="$t('kb.article_body_html_is_allowed')"
            required
          ></textarea>
          <p class="form-hint">{{ $t('kb.you_can_use_html_to_format') }}</p>
        </div>

        <div class="flex justify-end gap-3">
          <Link :href="route('tenant.manager.kb.index')" class="btn-secondary">{{ $t('common.cancel') }}</Link>
          <button type="submit" :disabled="form.processing" class="btn-primary">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
            {{ isEdit ? 'Zapisz zmiany' : $t('common.create_article') }}
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
  article: { type: Object, default: null },
  categories: { type: Array, default: () => [] },
})

const isEdit = computed(() => !!props.article)

const form = useForm({
  title: props.article?.title ?? '',
  category: props.article?.category ?? '',
  excerpt: props.article?.excerpt ?? '',
  content: props.article?.content ?? '',
  status: props.article?.status ?? 'draft',
})

const submit = () => {
  if (isEdit.value) {
    form.put(route('tenant.manager.kb.update', props.article.id))
  } else {
    form.post(route('tenant.manager.kb.store'))
  }
}
</script>
