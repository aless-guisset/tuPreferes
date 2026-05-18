<template>
  <AppLayout>
    <div class="admin-layout">
      <div class="admin-header">
        <h1 class="font-display admin-title">🚩 Signalements</h1>
        <div class="admin-nav">
          <Link :href="route('admin.index')"     class="admin-nav-btn">📊 Dashboard</Link>
          <Link :href="route('admin.users')"     class="admin-nav-btn">👥 Utilisateurs</Link>
          <Link :href="route('admin.posts')"     class="admin-nav-btn">📝 Posts</Link>
          <Link :href="route('admin.reports')"   class="admin-nav-btn active">🚩 Signalements</Link>
          <Link :href="route('admin.analytics')" class="admin-nav-btn">📈 Analytics</Link>
        </div>
      </div>

      <div v-if="reports.data.length === 0" class="empty-state card">
        <div class="empty-icon">✅</div>
        <h2 class="font-display">Aucun signalement en attente</h2>
        <p>Tout est propre !</p>
      </div>

      <div v-else class="reports-list">
        <div v-for="r in reports.data" :key="r.id" class="report-card card">
          <div class="report-header">
            <span class="reason-badge" :class="'reason-' + r.reason">{{ reasonLabel(r.reason) }}</span>
            <span class="report-type">{{ r.reportable_type }}</span>
            <span class="report-date">{{ r.created_at }}</span>
          </div>

          <div class="report-post">
            <span class="report-post-title">📄 {{ r.reportable_title }}</span>
            <span class="report-post-id text-muted">#{{ r.reportable_id }}</span>
          </div>

          <div v-if="r.comment" class="report-comment">
            💬 {{ r.comment }}
          </div>

          <div class="report-reporter">
            Signalé par <strong>{{ r.reporter.name }}</strong> ({{ r.reporter.email }})
          </div>

          <div class="report-actions">
            <!-- Masquer le post -->
            <button class="action-btn-md btn-orange" @click="hidePost(r)">
              🙈 Masquer le post
            </button>
            <!-- Supprimer le post -->
            <button class="action-btn-md btn-red" @click="deletePost(r)">
              🗑️ Supprimer le post
            </button>
            <!-- Résoudre sans action -->
            <button class="action-btn-md btn-green" @click="resolve(r.id, 'resolve')">
              ✅ Résolu
            </button>
            <!-- Rejeter le signalement -->
            <button class="action-btn-md btn-ghost" @click="resolve(r.id, 'reject')">
              ❌ Rejeter
            </button>
          </div>
        </div>
      </div>

      <div v-if="reports.last_page > 1" class="pagination">
        <button v-for="page in reports.last_page" :key="page" class="page-btn" :class="{ active: page === reports.current_page }" @click="goToPage(page)">{{ page }}</button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useToast } from '@/Composables/useToast'
import axios from 'axios'

const props = defineProps({ reports: Object })
const { add: toast } = useToast()

const reasonLabel = (r) => ({
  inapproprie:        '🔞 Inapproprié',
  spam:               '📨 Spam',
  'harcèlement':      '😡 Harcèlement',
  fausse_information: '❌ Fausse info',
  autre:              '❓ Autre',
})[r] ?? r

const resolve = async (id, action) => {
  try {
    await axios.patch(route('admin.reports.resolve', id), { action })
    toast(action === 'resolve' ? 'Signalement résolu.' : 'Signalement rejeté.')
    router.reload()
  } catch { toast('Erreur.', 'error') }
}

const hidePost = async (r) => {
  try {
    const type = r.reportable_type === 'Question' ? 'question' : r.reportable_type.toLowerCase()
    await axios.patch(route('admin.posts.hide', r.reportable_id), { type })
    await axios.patch(route('admin.reports.resolve', r.id), { action: 'resolve' })
    toast('Post masqué et signalement résolu.')
    router.reload()
  } catch { toast('Erreur.', 'error') }
}

const deletePost = async (r) => {
  if (!confirm('Supprimer ce post définitivement ?')) return
  try {
    const type = r.reportable_type === 'Question' ? 'question' : r.reportable_type.toLowerCase()
    await axios.delete(route('admin.posts.delete', r.reportable_id), { data: { type } })
    await axios.patch(route('admin.reports.resolve', r.id), { action: 'resolve' })
    toast('Post supprimé et signalement résolu.')
    router.reload()
  } catch { toast('Erreur.', 'error') }
}

const goToPage = (page) => router.get(route('admin.reports'), { page }, { preserveScroll: false })
</script>

<style scoped>
.admin-layout { max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.admin-header { display: flex; flex-direction: column; gap: 1rem; }
.admin-title { font-size: 1.75rem; font-weight: 800; margin: 0; }
.admin-nav { display: flex; gap: .5rem; flex-wrap: wrap; }
.admin-nav-btn { padding: .5rem 1rem; border: 1px solid var(--color-border); border-radius: var(--radius-md); color: var(--color-text-muted); text-decoration: none; font-size: .875rem; transition: all .2s; }
.admin-nav-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.admin-nav-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
.empty-state { padding: 3rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: .75rem; }
.empty-icon { font-size: 3rem; }
.empty-state h2 { font-size: 1.25rem; margin: 0; }
.empty-state p { color: var(--color-text-muted); margin: 0; }
.reports-list { display: flex; flex-direction: column; gap: 1rem; }
.report-card { padding: 1.5rem; display: flex; flex-direction: column; gap: .75rem; }
.report-header { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.reason-badge { padding: .25rem .75rem; border-radius: 999px; font-size: .78rem; font-weight: 600; }
.reason-inapproprie        { background: rgba(239,68,68,.15);   color: #ef4444; }
.reason-spam               { background: rgba(249,115,22,.15);  color: var(--color-accent); }
.reason-harcèlement        { background: rgba(236,72,153,.15);  color: #ec4899; }
.reason-fausse_information { background: rgba(99,102,241,.15);  color: #6366f1; }
.reason-autre              { background: var(--color-surface-2); color: var(--color-text-muted); }
.report-type { font-size: .78rem; color: var(--color-text-muted); background: var(--color-surface-2); padding: .2rem .6rem; border-radius: 999px; }
.report-date { font-size: .75rem; color: var(--color-text-faint); margin-left: auto; }
.report-post { display: flex; align-items: center; gap: .5rem; }
.report-post-title { font-weight: 600; font-size: .9rem; }
.report-post-id { font-size: .78rem; }
.report-comment { background: var(--color-surface-2); border-left: 3px solid var(--color-accent); padding: .6rem 1rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; font-size: .875rem; color: var(--color-text-muted); font-style: italic; }
.report-reporter { font-size: .8rem; color: var(--color-text-muted); }
.report-actions { display: flex; gap: .5rem; flex-wrap: wrap; padding-top: .5rem; border-top: 1px solid var(--color-border); }
.action-btn-md { padding: .4rem .9rem; border: none; border-radius: var(--radius-sm); font-size: .8rem; font-weight: 600; cursor: pointer; transition: opacity .2s; }
.action-btn-md:hover { opacity: .8; }
.btn-orange { background: rgba(249,115,22,.2); color: var(--color-accent); }
.btn-red    { background: rgba(239,68,68,.2);  color: #ef4444; }
.btn-green  { background: rgba(34,197,94,.2);  color: #22c55e; }
.btn-ghost  { background: var(--color-surface-2); color: var(--color-text-muted); }
.text-muted { color: var(--color-text-muted); }
.pagination { display: flex; justify-content: center; gap: .5rem; flex-wrap: wrap; }
.page-btn { width: 36px; height: 36px; border-radius: var(--radius-sm); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-muted); cursor: pointer; font-weight: 600; font-size: .875rem; transition: all .15s; }
.page-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.page-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
</style>
