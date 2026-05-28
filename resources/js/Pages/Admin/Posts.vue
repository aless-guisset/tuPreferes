<template>
  <AppLayout>
    <div class="admin-layout">
      <div class="admin-header">
        <h1 class="font-display admin-title">📝 {{ t('admin.posts') }}</h1>
        <div class="admin-nav">
          <Link :href="route('admin.index')"     class="admin-nav-btn">📊 {{ t('admin.dashboard') }}</Link>
          <Link :href="route('admin.users')"     class="admin-nav-btn">👥 {{ t('admin.users') }}</Link>
          <Link :href="route('admin.posts')"     class="admin-nav-btn active">📝 {{ t('admin.posts') }}</Link>
          <Link :href="route('admin.reports')"   class="admin-nav-btn">🚩 {{ t('admin.reports') }}</Link>
          <Link :href="route('admin.analytics')" class="admin-nav-btn">📈 {{ t('admin.analytics') }}</Link>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs-bar">
        <button class="tab-btn" :class="{ active: tab === 'questions' }" @click="tab = 'questions'">
          ❓ Questions ({{ questions.total }})
        </button>
        <button class="tab-btn" :class="{ active: tab === 'groups' }" @click="tab = 'groups'">
          📦 Groupes ({{ groups.total }})
        </button>
      </div>

      <!-- Search -->
      <div class="search-bar">
        <input v-model="search" type="text" class="field-input" :placeholder="t('admin.search_posts')" @input="doSearch" />
      </div>

      <!-- Questions table -->
      <div v-if="tab === 'questions'" class="table-wrap card">
        <table class="admin-table">
          <thead>
            <tr>
              <th>{{ t('admin.col_title_options') }}</th>
              <th>{{ t('admin.col_category') }}</th>
              <th>{{ t('admin.col_author') }}</th>
              <th>{{ t('admin.col_votes') }}</th>
              <th>{{ t('admin.col_reports') }}</th>
              <th>{{ t('admin.col_date') }}</th>
              <th>{{ t('admin.col_status') }}</th>
              <th>{{ t('admin.col_actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="q in questions.data" :key="q.id" :class="{ 'row-hidden': q.is_hidden }">
              <td>
                <div class="post-title">{{ q.title || t('admin.no_title') }}</div>
                <div class="post-type-badge" :class="'type-' + q.type">{{ q.type }}</div>
              </td>
              <td><span class="cat-badge">{{ q.category }}</span></td>
              <td class="text-muted">{{ q.author?.name ?? t('question.anonymous') }}</td>
              <td class="text-center">{{ q.votes_count }}</td>
              <td class="text-center">
                <span :class="{ 'text-red': q.reports_count > 0 }">{{ q.reports_count }}</span>
              </td>
              <td class="text-muted">{{ q.created_at }}</td>
              <td>
                <span class="status-badge" :class="q.is_hidden ? 'hidden' : 'visible'">
                  {{ q.is_hidden ? '🙈 ' + t('admin.hidden') : '👁️ ' + t('admin.visible') }}
                </span>
              </td>
              <td>
                <div class="action-btns">
                  <button class="action-btn-sm btn-orange" @click="toggleHide(q.id, 'question')">
                    {{ q.is_hidden ? t('admin.show') : t('admin.hide') }}
                  </button>
                  <button class="action-btn-sm btn-red" @click="deletePost(q.id, 'question')">
                    {{ t('admin.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="pagination">
          <button v-for="page in questions.last_page" :key="page" class="page-btn" :class="{ active: page === questions.current_page }" @click="goToPage(page, 'questions')">{{ page }}</button>
        </div>
      </div>

      <!-- Groups table -->
      <div v-if="tab === 'groups'" class="table-wrap card">
        <table class="admin-table">
          <thead>
            <tr>
              <th>{{ t('admin.col_title_options') }}</th>
              <th>{{ t('admin.col_type') }}</th>
              <th>{{ t('admin.col_category') }}</th>
              <th>{{ t('admin.col_author') }}</th>
              <th>{{ t('admin.col_reports') }}</th>
              <th>{{ t('admin.col_date') }}</th>
              <th>{{ t('admin.col_status') }}</th>
              <th>{{ t('admin.col_actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="g in groups.data" :key="g.id" :class="{ 'row-hidden': g.is_hidden }">
              <td><div class="post-title">{{ g.title }}</div></td>
              <td><span class="post-type-badge" :class="'type-' + g.type">{{ g.type }}</span></td>
              <td><span class="cat-badge">{{ g.category }}</span></td>
              <td class="text-muted">{{ g.author?.name ?? t('question.anonymous') }}</td>
              <td class="text-center"><span :class="{ 'text-red': g.reports_count > 0 }">{{ g.reports_count }}</span></td>
              <td class="text-muted">{{ g.created_at }}</td>
              <td>
                <span class="status-badge" :class="g.is_hidden ? 'hidden' : 'visible'">
                  {{ g.is_hidden ? '🙈 ' + t('admin.hidden') : '👁️ ' + t('admin.visible') }}
                </span>
              </td>
              <td>
                <div class="action-btns">
                  <button class="action-btn-sm btn-orange" @click="toggleHide(g.id, g.type)">
                    {{ g.is_hidden ? t('admin.show') : t('admin.hide') }}
                  </button>
                  <button class="action-btn-sm btn-red" @click="deletePost(g.id, g.type)">
                    {{ t('admin.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="pagination">
          <button v-for="page in groups.last_page" :key="page" class="page-btn" :class="{ active: page === groups.current_page }" @click="goToPage(page, 'groups')">{{ page }}</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useToast } from '@/Composables/useToast'
import { useI18n } from '@/Composables/useI18n'
import axios from 'axios'

const props  = defineProps({ questions: Object, groups: Object, filters: Object })
const { add: toast } = useToast()
const { t } = useI18n()
const tab    = ref('questions')
const search = ref(props.filters?.search ?? '')

let debounce = null
const doSearch = () => {
  clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get(route('admin.posts'), { search: search.value }, { preserveState: true, replace: true })
  }, 400)
}

const goToPage = (page, type) => {
  router.get(route('admin.posts'), { search: search.value, [`${type}_page`]: page }, { preserveScroll: false })
}

const toggleHide = async (id, type) => {
  try {
    await axios.patch(route('admin.posts.hide', id), { type })
    toast(t('admin.post_updated'))
    router.reload()
  } catch { toast(t('common.error'), 'error') }
}

const deletePost = async (id, type) => {
  if (!confirm(t('admin.confirm_delete_post'))) return
  try {
    await axios.delete(route('admin.posts.delete', id), { data: { type } })
    toast(t('admin.post_deleted'))
    router.reload()
  } catch { toast(t('common.error'), 'error') }
}
</script>

<style scoped>
.admin-layout { max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.admin-header { display: flex; flex-direction: column; gap: 1rem; }
.admin-title { font-size: 1.75rem; font-weight: 800; margin: 0; }
.admin-nav { display: flex; gap: .5rem; flex-wrap: wrap; }
.admin-nav-btn { padding: .5rem 1rem; border: 1px solid var(--color-border); border-radius: var(--radius-md); color: var(--color-text-muted); text-decoration: none; font-size: .875rem; transition: all .2s; }
.admin-nav-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.admin-nav-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
.tabs-bar { display: flex; gap: .5rem; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: .4rem; }
.tab-btn { flex: 1; padding: .6rem 1rem; border: none; border-radius: var(--radius-md); background: none; color: var(--color-text-muted); font-family: var(--font-body); font-size: .875rem; cursor: pointer; transition: all .2s; }
.tab-btn:hover { background: var(--color-surface-2); color: var(--color-text); }
.tab-btn.active { background: var(--color-accent-soft); color: var(--color-accent); font-weight: 600; }
.search-bar { max-width: 400px; }
.field-input { width: 100%; box-sizing: border-box; background: var(--color-surface-2); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: .7rem .9rem; color: var(--color-text); font-family: var(--font-body); font-size: .9rem; outline: none; transition: border-color .2s; }
.field-input:focus { border-color: var(--color-accent); }
.table-wrap { overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.admin-table th { padding: .75rem 1rem; text-align: left; font-size: .75rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
.admin-table td { padding: .75rem 1rem; border-bottom: 1px solid var(--color-border); color: var(--color-text); vertical-align: middle; }
.admin-table tr:last-child td { border-bottom: none; }
.admin-table tr:hover td { background: var(--color-surface-2); }
.row-hidden td { opacity: .5; }
.post-title { font-weight: 600; font-size: .875rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.post-type-badge { display: inline-block; font-size: .7rem; font-weight: 600; padding: .15rem .5rem; border-radius: 999px; margin-top: .2rem; text-transform: capitalize; }
.type-simple { background: rgba(99,102,241,.15); color: #6366f1; }
.type-group { background: rgba(249,115,22,.15); color: var(--color-accent); }
.type-elimination { background: rgba(245,158,11,.15); color: #f59e0b; }
.cat-badge { background: var(--color-accent-soft); color: var(--color-accent); padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600; text-transform: capitalize; }
.text-muted { color: var(--color-text-muted); }
.text-center { text-align: center; }
.text-red { color: #ef4444; font-weight: 700; }
.status-badge { padding: .2rem .6rem; border-radius: 999px; font-size: .75rem; font-weight: 600; }
.status-badge.visible { background: rgba(34,197,94,.15); color: #22c55e; }
.status-badge.hidden  { background: rgba(239,68,68,.15);  color: #ef4444; }
.action-btns { display: flex; gap: .4rem; }
.action-btn-sm { padding: .25rem .6rem; border: none; border-radius: var(--radius-sm); font-size: .75rem; font-weight: 600; cursor: pointer; transition: opacity .2s; white-space: nowrap; }
.action-btn-sm:hover { opacity: .8; }
.btn-orange { background: rgba(249,115,22,.2); color: var(--color-accent); }
.btn-red    { background: rgba(239,68,68,.2);  color: #ef4444; }
.pagination { display: flex; justify-content: center; gap: .5rem; padding: 1rem; flex-wrap: wrap; }
.page-btn { width: 36px; height: 36px; border-radius: var(--radius-sm); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-muted); cursor: pointer; font-weight: 600; font-size: .875rem; transition: all .15s; }
.page-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.page-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
</style>
