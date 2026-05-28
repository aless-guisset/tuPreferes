<template>
  <AppLayout>
    <div class="admin-layout">
      <!-- Header -->
      <div class="admin-header">
        <h1 class="font-display admin-title">🛡️ {{ t('admin.title') }}</h1>
        <div class="admin-nav">
          <Link :href="route('admin.index')"      class="admin-nav-btn" :class="{ active: isRoute('admin.index') }">📊 {{ t('admin.dashboard') }}</Link>
          <Link :href="route('admin.users')"      class="admin-nav-btn" :class="{ active: isRoute('admin.users') }">👥 {{ t('admin.users') }}</Link>
          <Link :href="route('admin.posts')"      class="admin-nav-btn" :class="{ active: isRoute('admin.posts') }">📝 {{ t('admin.posts') }}</Link>
          <Link :href="route('admin.reports')"    class="admin-nav-btn" :class="{ active: isRoute('admin.reports') }">🚩 {{ t('admin.reports') }} <span v-if="stats.total_reports > 0" class="badge">{{ stats.total_reports }}</span></Link>
          <Link :href="route('admin.analytics')"  class="admin-nav-btn" :class="{ active: isRoute('admin.analytics') }">📈 {{ t('admin.analytics') }}</Link>
        </div>
      </div>

      <!-- Stats grid -->
      <div class="stats-grid">
        <div class="stat-card card">
          <div class="stat-icon">👥</div>
          <div class="stat-info">
            <span class="stat-num font-display">{{ stats.total_users }}</span>
            <span class="stat-label">{{ t('admin.stat_users') }}</span>
          </div>
          <span class="stat-sub">{{ t('admin.new_today').replace('{n}', stats.new_users_today) }}</span>
        </div>
        <div class="stat-card card">
          <div class="stat-icon">❓</div>
          <div class="stat-info">
            <span class="stat-num font-display">{{ stats.total_questions }}</span>
            <span class="stat-label">{{ t('admin.stat_questions') }}</span>
          </div>
          <span class="stat-sub">{{ t('admin.groups_count').replace('{n}', stats.total_groups) }}</span>
        </div>
        <div class="stat-card card">
          <div class="stat-icon">🗳️</div>
          <div class="stat-info">
            <span class="stat-num font-display">{{ stats.total_votes }}</span>
            <span class="stat-label">{{ t('admin.stat_votes') }}</span>
          </div>
          <span class="stat-sub">{{ t('admin.new_today').replace('{n}', stats.new_votes_today) }}</span>
        </div>
        <div class="stat-card card">
          <div class="stat-icon">🚩</div>
          <div class="stat-info">
            <span class="stat-num font-display" :class="{ danger: stats.total_reports > 0 }">{{ stats.total_reports }}</span>
            <span class="stat-label">{{ t('admin.stat_reports') }}</span>
          </div>
          <span class="stat-sub">{{ stats.banned_users }} {{ t('admin.banned').toLowerCase() }}</span>
        </div>
        <div class="stat-card card">
          <div class="stat-icon">🙈</div>
          <div class="stat-info">
            <span class="stat-num font-display">{{ stats.hidden_posts }}</span>
            <span class="stat-label">{{ t('admin.stat_hidden') }}</span>
          </div>
          <span class="stat-sub">{{ t('admin.likes_total').replace('{n}', stats.total_likes) }}</span>
        </div>
      </div>

      <!-- Actions rapides -->
      <div class="quick-actions card">
        <h2 class="font-display section-title">{{ t('admin.quick_actions') }}</h2>
        <div class="actions-row">
          <Link :href="route('admin.reports')" class="btn-primary" v-if="stats.total_reports > 0">
            🚩 {{ tp('admin.handle_reports', stats.total_reports) }}
          </Link>
          <Link :href="route('admin.users')" class="btn-ghost">👥 {{ t('admin.manage_users') }}</Link>
          <Link :href="route('admin.posts')" class="btn-ghost">📝 {{ t('admin.moderate_posts') }}</Link>
          <Link :href="route('admin.analytics')" class="btn-ghost">📈 {{ t('admin.view_analytics') }}</Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useI18n } from '@/Composables/useI18n'

defineProps({ stats: Object })
const { t, tp } = useI18n()
const isRoute = (name) => route().current(name)
</script>

<style scoped>
.admin-layout { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.admin-header { display: flex; flex-direction: column; gap: 1rem; }
.admin-title { font-size: 1.75rem; font-weight: 800; margin: 0; }
.admin-nav { display: flex; gap: .5rem; flex-wrap: wrap; }
.admin-nav-btn { padding: .5rem 1rem; border: 1px solid var(--color-border); border-radius: var(--radius-md); color: var(--color-text-muted); text-decoration: none; font-size: .875rem; transition: all .2s; display: flex; align-items: center; gap: .4rem; }
.admin-nav-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.admin-nav-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
.badge { background: #ef4444; color: white; border-radius: 999px; padding: .1rem .45rem; font-size: .7rem; font-weight: 700; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.stat-card { padding: 1.25rem; display: flex; flex-direction: column; gap: .5rem; }
.stat-icon { font-size: 1.75rem; }
.stat-info { display: flex; flex-direction: column; gap: .1rem; }
.stat-num { font-size: 2rem; font-weight: 800; color: var(--color-accent); }
.stat-num.danger { color: #ef4444; }
.stat-label { font-size: .8rem; color: var(--color-text-muted); }
.stat-sub { font-size: .72rem; color: var(--color-text-faint); }
.quick-actions { padding: 1.5rem; }
.section-title { font-size: 1rem; font-weight: 800; margin: 0 0 1rem; text-transform: uppercase; letter-spacing: .05em; color: var(--color-text-muted); }
.actions-row { display: flex; gap: .75rem; flex-wrap: wrap; }
@media (max-width: 600px) { .stats-grid { grid-template-columns: 1fr 1fr; } }
</style>
