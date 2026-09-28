<template>
  <ManagerLayout :title="$t('settings.title')">
    <div class="max-w-5xl space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('settings.title') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.subtitle') }}</p>
      </div>

      <section v-for="group in groups" :key="group.heading" class="space-y-3">
        <h2 class="text-sm font-semibold text-gray-700">{{ group.heading }}</h2>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <Link
            v-for="card in group.cards"
            :key="card.route"
            :href="route(card.route)"
            class="bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm transition"
          >
            <div class="flex items-start gap-3">
              <span class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <i :class="card.icon"></i>
              </span>
              <span>
                <span class="block text-sm font-medium text-gray-900">{{ card.title }}</span>
                <span class="block text-xs text-gray-500 mt-0.5">{{ card.description }}</span>
              </span>
            </div>
          </Link>
        </div>
      </section>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const { t } = useI18n()

/**
 * A hub rather than a form: every one of these screens owns its own state, so
 * putting them behind one giant page only made them harder to find.
 */
const groups = computed(() => [
  {
    heading: t('settings.group_workspace'),
    cards: [
      {
        route: 'tenant.manager.settings.company',
        icon: 'fa-solid fa-building',
        title: t('settings.company_title'),
        description: t('settings.company_description'),
      },
      {
        route: 'tenant.manager.settings.notifications',
        icon: 'fa-solid fa-bell',
        title: t('settings.notifications_title'),
        description: t('settings.notifications_description'),
      },
      {
        route: 'tenant.manager.settings.integrations.index',
        icon: 'fa-solid fa-plug',
        title: t('settings.integrations_title'),
        description: t('settings.integrations_description'),
      },
      {
        route: 'tenant.manager.webhooks.index',
        icon: 'fa-solid fa-satellite-dish',
        title: t('settings.webhooks_title'),
        description: t('settings.webhooks_description'),
      },
    ],
  },
  {
    heading: t('settings.group_work'),
    cards: [
      {
        route: 'tenant.manager.task-statuses.index',
        icon: 'fa-solid fa-list-check',
        title: t('settings.task_statuses_title'),
        description: t('settings.task_statuses_description'),
      },
      {
        route: 'tenant.manager.task-labels.index',
        icon: 'fa-solid fa-tags',
        title: t('settings.task_labels_title'),
        description: t('settings.task_labels_description'),
      },
      {
        route: 'tenant.manager.settings.custom-fields.index',
        icon: 'fa-solid fa-square-plus',
        title: t('settings.custom_fields_title'),
        description: t('settings.custom_fields_description'),
      },
      {
        route: 'tenant.manager.client-groups.index',
        icon: 'fa-solid fa-user-group',
        title: t('settings.client_groups_title'),
        description: t('settings.client_groups_description'),
      },
    ],
  },
  {
    heading: t('settings.group_finance'),
    cards: [
      {
        route: 'tenant.manager.settings.finance',
        icon: 'fa-solid fa-coins',
        title: t('settings.finance_title'),
        description: t('settings.finance_description'),
      },
      {
        route: 'tenant.manager.tax-rates.index',
        icon: 'fa-solid fa-percent',
        title: t('settings.tax_rates_title'),
        description: t('settings.tax_rates_description'),
      },
      {
        route: 'tenant.manager.currencies.index',
        icon: 'fa-solid fa-money-bill-transfer',
        title: t('settings.currencies_title'),
        description: t('settings.currencies_description'),
      },
      {
        route: 'tenant.manager.payment-methods.index',
        icon: 'fa-solid fa-credit-card',
        title: t('settings.payment_methods_title'),
        description: t('settings.payment_methods_description'),
      },
    ],
  },
  {
    heading: t('settings.group_people'),
    cards: [
      {
        route: 'tenant.manager.departments.index',
        icon: 'fa-solid fa-sitemap',
        title: t('settings.departments_title'),
        description: t('settings.departments_description'),
      },
      {
        route: 'tenant.manager.positions.index',
        icon: 'fa-solid fa-id-badge',
        title: t('settings.positions_title'),
        description: t('settings.positions_description'),
      },
      {
        route: 'tenant.manager.leave-types.index',
        icon: 'fa-solid fa-umbrella-beach',
        title: t('settings.leave_types_title'),
        description: t('settings.leave_types_description'),
      },
      {
        route: 'tenant.manager.permissions.index',
        icon: 'fa-solid fa-shield-halved',
        title: t('settings.permissions_title'),
        description: t('settings.permissions_description'),
      },
    ],
  },
  {
    heading: t('settings.group_communication'),
    cards: [
      {
        route: 'tenant.manager.settings.email-templates.index',
        icon: 'fa-solid fa-envelope-open-text',
        title: t('settings.email_templates_title'),
        description: t('settings.email_templates_description'),
      },
      {
        route: 'tenant.manager.settings.billing',
        icon: 'fa-solid fa-receipt',
        title: t('settings.billing_title'),
        description: t('settings.billing_description'),
      },
      {
        route: 'tenant.manager.settings.audit-log',
        icon: 'fa-solid fa-clock-rotate-left',
        title: t('settings.audit_log_title'),
        description: t('settings.audit_log_description'),
      },
    ],
  },
])
</script>
