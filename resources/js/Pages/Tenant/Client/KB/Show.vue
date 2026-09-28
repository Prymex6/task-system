<template>
  <ClientLayout :title="article.title">
    <div class="space-y-5 max-w-3xl">
      <div class="flex items-center gap-2 text-sm text-gray-500">
        <Link :href="route('tenant.portal.kb')" class="text-indigo-600 hover:text-indigo-700">{{
          $t('common.knowledge_base')
        }}</Link>
        <span>/</span>
        <span>{{ article.title }}</span>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="mb-4">
          <span v-if="article.category" class="badge badge-indigo text-xs mb-2">{{ article.category }}</span>
          <h1 class="text-2xl font-bold text-gray-900">{{ article.title }}</h1>
          <div class="flex gap-4 text-xs text-gray-400 mt-2">
            <span><i class="fa-solid fa-eye mr-1"></i>{{ article.views_count }} {{ $t('portal.views') }}</span>
            <span><i class="fa-solid fa-calendar mr-1"></i>{{ formatDate(article.updated_at) }}</span>
          </div>
        </div>
        <div class="prose prose-sm max-w-none text-gray-700" v-html="article.content"></div>
      </div>

      <!-- Related articles -->
      <div v-if="related?.length" class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-900 mb-3">{{ $t('portal.related_articles') }}</h3>
        <div class="space-y-2">
          <Link
            v-for="rel in related"
            :key="rel.id"
            :href="route('tenant.portal.kb.show', rel.slug)"
            class="block text-sm text-indigo-600 hover:text-indigo-700 hover:underline"
          >
            {{ rel.title }}
          </Link>
        </div>
      </div>

      <div class="flex justify-between">
        <Link :href="route('tenant.portal.kb')" class="btn-secondary btn-sm">
          {{ $t('portal.back_to_the_knowledge_base') }}
        </Link>
        <Link :href="route('tenant.portal.tickets.create')" class="btn-ghost btn-sm text-gray-500">
          {{ $t('portal.still_stuck_open_a_ticket') }}
        </Link>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

const props = defineProps({
  article: Object,
  related: { type: Array, default: () => [] },
})

const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
