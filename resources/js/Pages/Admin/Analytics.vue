<template>
  <AppLayout>
    <div class="admin-layout">
      <div class="admin-header">
        <h1 class="font-display admin-title">📈 {{ t('admin.analytics') }}</h1>
        <div class="admin-nav">
          <Link :href="route('admin.index')"     class="admin-nav-btn">📊 {{ t('admin.dashboard') }}</Link>
          <Link :href="route('admin.users')"     class="admin-nav-btn">👥 {{ t('admin.users') }}</Link>
          <Link :href="route('admin.posts')"     class="admin-nav-btn">📝 {{ t('admin.posts') }}</Link>
          <Link :href="route('admin.reports')"   class="admin-nav-btn">🚩 {{ t('admin.reports') }}</Link>
          <Link :href="route('admin.analytics')" class="admin-nav-btn active">📈 {{ t('admin.analytics') }}</Link>
        </div>
      </div>

      <!-- Stats rapides -->
      <div class="stats-grid">
        <div class="stat-card card">
          <span class="stat-icon">👥</span>
          <span class="stat-num font-display">{{ stats.total_users }}</span>
          <span class="stat-label">{{ t('admin.total_users') }}</span>
        </div>
        <div class="stat-card card">
          <span class="stat-icon">📝</span>
          <span class="stat-num font-display">{{ stats.total_questions }}</span>
          <span class="stat-label">{{ t('admin.total_questions') }}</span>
        </div>
        <div class="stat-card card">
          <span class="stat-icon">🗳️</span>
          <span class="stat-num font-display">{{ stats.total_votes }}</span>
          <span class="stat-label">{{ t('admin.total_votes') }}</span>
        </div>
        <div class="stat-card card">
          <span class="stat-icon">🆕</span>
          <span class="stat-num font-display">{{ stats.new_users_today }}</span>
          <span class="stat-label">{{ t('admin.new_today').replace('{n}', '') }}</span>
        </div>
      </div>

      <!-- Switch graphique / tableau -->
      <div class="view-switch">
        <button class="switch-btn" :class="{ active: view === 'chart' }" @click="view = 'chart'">
          📊 {{ t('admin.chart') }}
        </button>
        <button class="switch-btn" :class="{ active: view === 'table' }" @click="view = 'table'">
          📋 {{ t('admin.table') }}
        </button>
      </div>

      <!-- Sélecteur de métrique -->
      <div class="metric-selector">
        <button class="metric-btn" :class="{ active: metric === 'registrations' }" @click="metric = 'registrations'">👥 {{ t('admin.metric_registrations') }}</button>
        <button class="metric-btn" :class="{ active: metric === 'votes' }" @click="metric = 'votes'">🗳️ {{ t('admin.metric_votes') }}</button>
        <button class="metric-btn" :class="{ active: metric === 'questions' }" @click="metric = 'questions'">❓ {{ t('admin.metric_questions') }}</button>
      </div>

      <!-- GRAPHIQUE -->
      <div v-if="view === 'chart'" class="chart-card card">
        <h3 class="font-display chart-title">{{ metricLabel }} {{ t('admin.last_30_days_suffix') }}</h3>
        <div class="chart-wrap">
          <div class="chart-bars">
            <div
              v-for="(item, i) in currentData"
              :key="i"
              class="chart-col"
            >
              <div class="bar-wrap">
                <div
                  class="bar"
                  :style="{ height: barHeight(item.count) + '%' }"
                  :title="item.date + ' : ' + item.count"
                />
              </div>
              <span class="bar-label">{{ shortDate(item.date) }}</span>
            </div>
          </div>
          <div class="chart-y-axis">
            <span>{{ maxVal }}</span>
            <span>{{ Math.round(maxVal / 2) }}</span>
            <span>0</span>
          </div>
        </div>
      </div>

      <!-- TABLEAU -->
      <div v-if="view === 'table'" class="table-wrap card">
        <h3 class="font-display chart-title" style="padding:1.25rem 1.25rem 0">{{ metricLabel }} {{ t('admin.last_30_days_suffix') }}</h3>
        <table class="admin-table">
          <thead>
            <tr>
              <th>{{ t('admin.col_date') }}</th>
              <th>{{ metricLabel }}</th>
              <th>{{ t('admin.registrations') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, i) in [...currentData].reverse()" :key="i">
              <td>{{ item.date }}</td>
              <td class="text-center font-display" style="font-weight:800;color:var(--color-accent)">{{ item.count }}</td>
              <td>
                <span v-if="i < currentData.length - 1" :class="trend(item.count, currentData[currentData.length - 2 - i]?.count)">
                  {{ trendIcon(item.count, currentData[currentData.length - 2 - i]?.count) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useI18n } from '@/Composables/useI18n'

const props = defineProps({
  stats: Object,
  registrations: Array,
  votes: Array,
  questions: Array,
})

const { t } = useI18n()
const view   = ref('chart')
const metric = ref('registrations')

const currentData = computed(() => ({
  registrations: props.registrations,
  votes:         props.votes,
  questions:     props.questions,
})[metric.value] ?? [])

const metricLabel = computed(() => ({
  registrations: t('admin.metric_registrations'),
  votes:         t('admin.metric_votes'),
  questions:     t('admin.metric_questions'),
})[metric.value])

const maxVal = computed(() => Math.max(...currentData.value.map(d => d.count), 1))

const barHeight = (count) => Math.max((count / maxVal.value) * 100, 2)

const shortDate = (date) => {
  const d = new Date(date)
  return `${d.getDate()}/${d.getMonth() + 1}`
}

const trend = (current, prev) => {
  if (prev === undefined) return ''
  return current >= prev ? 'text-green' : 'text-red'
}

const trendIcon = (current, prev) => {
  if (prev === undefined) return ''
  if (current > prev)  return `↑ +${current - prev}`
  if (current < prev)  return `↓ -${prev - current}`
  return '→ 0'
}
</script>

<style scoped>
.admin-layout { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.admin-header { display: flex; flex-direction: column; gap: 1rem; }
.admin-title { font-size: 1.75rem; font-weight: 800; margin: 0; }
.admin-nav { display: flex; gap: .5rem; flex-wrap: wrap; }
.admin-nav-btn { padding: .5rem 1rem; border: 1px solid var(--color-border); border-radius: var(--radius-md); color: var(--color-text-muted); text-decoration: none; font-size: .875rem; transition: all .2s; }
.admin-nav-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.admin-nav-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr)); gap: 1rem; }
.stat-card { padding: 1.25rem; display: flex; flex-direction: column; gap: .4rem; align-items: center; text-align: center; }
.stat-icon { font-size: 1.75rem; }
.stat-num { font-size: 2rem; font-weight: 800; color: var(--color-accent); }
.stat-label { font-size: .8rem; color: var(--color-text-muted); }
.view-switch { display: flex; gap: .5rem; }
.switch-btn { padding: .5rem 1.25rem; border: 1px solid var(--color-border); border-radius: var(--radius-md); background: none; color: var(--color-text-muted); font-family: var(--font-body); font-size: .875rem; cursor: pointer; transition: all .2s; }
.switch-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.switch-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
.metric-selector { display: flex; gap: .5rem; flex-wrap: wrap; }
.metric-btn { padding: .4rem .9rem; border: 1px solid var(--color-border); border-radius: 999px; background: none; color: var(--color-text-muted); font-family: var(--font-body); font-size: .825rem; cursor: pointer; transition: all .15s; }
.metric-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.metric-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
.chart-card { padding: 1.5rem; }
.chart-title { font-size: 1rem; font-weight: 800; margin: 0 0 1.5rem; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .05em; }
.chart-wrap { display: flex; gap: .5rem; align-items: flex-end; }
.chart-bars { flex: 1; display: flex; align-items: flex-end; gap: 3px; height: 200px; }
.chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: .25rem; height: 100%; }
.bar-wrap { flex: 1; width: 100%; display: flex; align-items: flex-end; }
.bar { width: 100%; background: var(--color-accent); border-radius: 3px 3px 0 0; transition: height .5s ease; min-height: 2px; }
.bar-label { font-size: .55rem; color: var(--color-text-faint); white-space: nowrap; }
.chart-y-axis { display: flex; flex-direction: column; justify-content: space-between; padding-bottom: 1.2rem; font-size: .7rem; color: var(--color-text-faint); text-align: right; min-width: 30px; }
.table-wrap { overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.admin-table th { padding: .75rem 1rem; text-align: left; font-size: .75rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--color-border); }
.admin-table td { padding: .75rem 1rem; border-bottom: 1px solid var(--color-border); color: var(--color-text); }
.admin-table tr:last-child td { border-bottom: none; }
.text-center { text-align: center; }
.text-green { color: #22c55e; font-weight: 600; }
.text-red   { color: #ef4444; font-weight: 600; }
@media (max-width: 600px) { .chart-bars { gap: 1px; } .bar-label { display: none; } }
</style>
