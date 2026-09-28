<template>
  <ClientLayout :title="$t('common.knowledge_base')">
    <div class="space-y-5">
      <div>
        <h1 class="text-xl font-bold text-gray-900">{{ $t('common.knowledge_base') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $t('portal.answers_to_the_usual_questions') }}</p>
      </div>

      <!-- Search -->
      <div class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input
          v-model="search"
          type="text"
          class="input pl-10"
          :placeholder="$t('portal.search_the_knowledge_base')"
          @input="doSearch"
        />
      </div>

      <!-- Categories -->
      <div v-if="categories.length" class="flex flex-wrap gap-2">
        <button
          v-for="cat in ['Wszystkie', ...categories]"
          :key="cat"
          @click="selectedCategory = cat === 'Wszystkie' ? '' : cat"
          class="px-3 py-1.5 text-sm rounded-lg border transition-colors"
          :class="
            (cat === 'Wszystkie' && !selectedCategory) || selectedCategory === cat
              ? 'bg-indigo-600 text-white border-indigo-600'
              : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300'
          "
        >
          {{ cat }}
        </button>
      </div>

      <!-- Articles -->
      <div class="space-y-3">
        <div v-if="!filteredArticles.length" class="bg-white rounded-xl border border-gray-200 p-8 text-center">
          <div class="text-3xl mb-2">🔍</div>
          <div class="text-gray-500">{{ $t('portal.no_articles_found') }}</div>
        </div>

        <Link
          v-for="article in filteredArticles"
          :key="article.id"
          :href="route('tenant.portal.kb.show', article.slug)"
          class="block bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md hover:border-indigo-200 transition-all"
        >
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600">{{ article.title }}</h3>
              <p v-if="article.excerpt" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ article.excerpt }}</p>
            </div>
            <div class="flex flex-col items-end gap-1 flex-shrink-0">
              <span v-if="article.category" class="badge badge-gray text-xs">{{ article.category }}</span>
              <span class="text-xs text-gray-400">{{ article.views_count }} {{ $t('portal.views') }}</span>
            </div>
          </div>
        </Link>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

const props = defineProps({
  articles: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const search = ref('')
const selectedCategory = ref('')

const filteredArticles = computed(() => {
  return props.articles.filter((a) => {
    const matchSearch =
      !search.value ||
      a.title.toLowerCase().includes(search.value.toLowerCase()) ||
      a.content?.toLowerCase().includes(search.value.toLowerCase())
    const matchCat = !selectedCategory.value || a.category === selectedCategory.value
    return matchSearch && matchCat
  })
})

const doSearch = () => {} // filtering is computed
</script>
