<template>
  <AppLayout>
    <div class="admin-layout">
      <!-- Header nav -->
      <div class="admin-header">
        <h1 class="font-display admin-title">👥 Utilisateurs</h1>
        <div class="admin-nav">
          <Link :href="route('admin.index')"     class="admin-nav-btn">📊 Dashboard</Link>
          <Link :href="route('admin.users')"     class="admin-nav-btn active">👥 Utilisateurs</Link>
          <Link :href="route('admin.posts')"     class="admin-nav-btn">📝 Posts</Link>
          <Link :href="route('admin.reports')"   class="admin-nav-btn">🚩 Signalements</Link>
          <Link :href="route('admin.analytics')" class="admin-nav-btn">📈 Analytics</Link>
        </div>
      </div>

      <!-- Search -->
      <div class="search-bar">
        <input v-model="search" type="text" class="field-input" placeholder="Rechercher par nom ou email..." @input="doSearch" />
      </div>

      <!-- Table -->
      <div class="table-wrap card">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Utilisateur</th>
              <th>Email</th>
              <th>Rôle</th>
              <th>Questions</th>
              <th>Votes</th>
              <th>Inscrit le</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users.data" :key="user.id" :class="{ 'row-banned': user.banned }">
              <td>
                <div class="user-cell">
                  <img :src="user.avatar_url" class="user-avatar" />
                  <div>
                    <div class="user-name">{{ user.name }}</div>
                    <div class="user-username">@{{ user.username }}</div>
                  </div>
                </div>
              </td>
              <td class="text-muted">{{ user.email }}</td>
              <td>
                <select class="role-select" :value="user.role" @change="updateRole(user.id, $event.target.value)">
                  <option value="user">👤 User</option>
                  <option value="admin">🛡️ Admin</option>
                </select>
              </td>
              <td class="text-center">{{ user.questions_count }}</td>
              <td class="text-center">{{ user.votes_count }}</td>
              <td class="text-muted">{{ user.created_at }}</td>
              <td>
                <span class="status-badge" :class="user.banned ? 'banned' : 'active'">
                  {{ user.banned ? '🔴 Banni' : '🟢 Actif' }}
                </span>
              </td>
              <td>
                <div class="action-btns">
                  <button class="action-btn-sm" :class="user.banned ? 'btn-green' : 'btn-orange'" @click="toggleBan(user)">
                    {{ user.banned ? 'Débannir' : 'Bannir' }}
                  </button>
                  <button class="action-btn-sm btn-red" @click="deleteUser(user)" v-if="user.id !== $page.props.auth.user.id">
                    Supprimer
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination">
        <button v-for="page in users.last_page" :key="page" class="page-btn" :class="{ active: page === users.current_page }" @click="goToPage(page)">{{ page }}</button>
      </div>

      <!-- Modal ban -->
      <div v-if="banModal.show" class="modal-overlay" @click.self="banModal.show = false">
        <div class="modal card">
          <h3 class="font-display modal-title">Bannir {{ banModal.user?.name }}</h3>
          <div class="field">
            <label class="field-label">Raison (optionnel)</label>
            <input v-model="banModal.reason" type="text" class="field-input" placeholder="Raison du bannissement..." />
          </div>
          <div class="modal-actions">
            <button class="btn-ghost" @click="banModal.show = false">Annuler</button>
            <button class="btn-primary" style="background:#ef4444" @click="confirmBan">Confirmer le ban</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useToast } from '@/Composables/useToast'
import axios from 'axios'

const props = defineProps({ users: Object, filters: Object })
const { add: toast } = useToast()
const search = ref(props.filters?.search ?? '')

let debounce = null
const doSearch = () => {
  clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get(route('admin.users'), { search: search.value }, { preserveState: true, replace: true })
  }, 400)
}

const goToPage = (page) => router.get(route('admin.users'), { search: search.value, page }, { preserveScroll: false })

const updateRole = async (userId, role) => {
  try {
    await axios.patch(route('admin.users.role', userId), { role })
    toast('Rôle mis à jour.')
  } catch { toast('Erreur.', 'error') }
}

const banModal = reactive({ show: false, user: null, reason: '' })

const toggleBan = (user) => {
  if (user.banned) {
    confirmBanDirect(user)
  } else {
    banModal.user   = user
    banModal.reason = ''
    banModal.show   = true
  }
}

const confirmBanDirect = async (user) => {
  try {
    await axios.patch(route('admin.users.ban', user.id), { reason: '' })
    toast('Bannissement levé.')
    router.reload()
  } catch { toast('Erreur.', 'error') }
}

const confirmBan = async () => {
  try {
    await axios.patch(route('admin.users.ban', banModal.user.id), { reason: banModal.reason })
    toast('Utilisateur banni.')
    banModal.show = false
    router.reload()
  } catch { toast('Erreur.', 'error') }
}

const deleteUser = async (user) => {
  if (!confirm('Supprimer définitivement ' + user.name + ' ?')) return
  try {
    await axios.delete(route('admin.users.delete', user.id))
    toast('Utilisateur supprimé.')
    router.reload()
  } catch { toast('Erreur.', 'error') }
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
.search-bar { max-width: 400px; }
.field-input { width: 100%; box-sizing: border-box; background: var(--color-surface-2); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: .7rem .9rem; color: var(--color-text); font-family: var(--font-body); font-size: .9rem; outline: none; transition: border-color .2s; }
.field-input:focus { border-color: var(--color-accent); }
.table-wrap { overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.admin-table th { padding: .75rem 1rem; text-align: left; font-size: .75rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--color-border); }
.admin-table td { padding: .75rem 1rem; border-bottom: 1px solid var(--color-border); color: var(--color-text); vertical-align: middle; }
.admin-table tr:last-child td { border-bottom: none; }
.admin-table tr:hover td { background: var(--color-surface-2); }
.row-banned td { opacity: .6; }
.user-cell { display: flex; align-items: center; gap: .6rem; }
.user-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.user-name { font-weight: 600; font-size: .875rem; }
.user-username { font-size: .75rem; color: var(--color-text-muted); }
.text-muted { color: var(--color-text-muted); }
.text-center { text-align: center; }
.role-select { background: var(--color-surface-2); border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: .3rem .5rem; color: var(--color-text); font-size: .8rem; cursor: pointer; }
.status-badge { padding: .2rem .6rem; border-radius: 999px; font-size: .75rem; font-weight: 600; }
.status-badge.active { background: rgba(34,197,94,.15); color: #22c55e; }
.status-badge.banned { background: rgba(239,68,68,.15); color: #ef4444; }
.action-btns { display: flex; gap: .4rem; }
.action-btn-sm { padding: .25rem .6rem; border: none; border-radius: var(--radius-sm); font-size: .75rem; font-weight: 600; cursor: pointer; transition: opacity .2s; }
.action-btn-sm:hover { opacity: .8; }
.btn-orange { background: rgba(249,115,22,.2); color: var(--color-accent); }
.btn-green  { background: rgba(34,197,94,.2);  color: #22c55e; }
.btn-red    { background: rgba(239,68,68,.2);  color: #ef4444; }
.pagination { display: flex; justify-content: center; gap: .5rem; flex-wrap: wrap; }
.page-btn { width: 36px; height: 36px; border-radius: var(--radius-sm); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-muted); cursor: pointer; font-weight: 600; font-size: .875rem; transition: all .15s; }
.page-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.page-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.modal { padding: 2rem; max-width: 440px; width: 100%; display: flex; flex-direction: column; gap: 1.25rem; }
.modal-title { font-size: 1.25rem; font-weight: 800; margin: 0; }
.field { display: flex; flex-direction: column; gap: .35rem; }
.field-label { font-size: .8rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .04em; }
.modal-actions { display: flex; justify-content: flex-end; gap: .75rem; }
</style>
