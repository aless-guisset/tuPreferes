<template>
  <AppLayout>
    <div class="followers-layout">
      <div class="followers-header">
        <Link :href="route('profile.public', profileUser.id)" class="back-link">← @{{ profileUser.username }}</Link>
        <div class="tabs-bar">
          <Link :href="route('profile.followers', profileUser.id)" class="tab-btn" :class="{ active: tab === 'followers' }">Abonnés</Link>
          <Link :href="route('profile.following', profileUser.id)" class="tab-btn" :class="{ active: tab === 'following' }">Abonnements</Link>
        </div>
      </div>

      <div class="users-list">
        <div v-for="user in followers.data" :key="user.id" class="user-card card">
          <Link :href="route('profile.public', user.id)" class="user-info">
            <img :src="user.avatar_url" :alt="user.name" class="user-avatar" />
            <div>
              <div class="user-name">{{ user.name }}</div>
              <div class="user-username">@{{ user.username }}</div>
              <div class="user-stats">{{ user.followers_count }} abonnés</div>
            </div>
          </Link>
          <button
            v-if="$page.props.auth.user && $page.props.auth.user.id !== user.id"
            class="btn-ghost follow-btn-sm"
            :class="{ following: user.is_following }"
            @click="toggleFollow(user)"
          >
            {{ user.is_following ? '✓ Abonné' : '+ Suivre' }}
          </button>
        </div>
      </div>

      <div v-if="followers.data.length === 0" class="empty-state card">
        <p>{{ tab === 'followers' ? 'Aucun abonné pour l\'instant.' : 'Aucun abonnement pour l\'instant.' }}</p>
      </div>

      <div v-if="followers.last_page > 1" class="pagination">
        <button v-for="page in followers.last_page" :key="page" class="page-btn" :class="{ active: page === followers.current_page }" @click="goToPage(page)">{{ page }}</button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

const props = defineProps({ profileUser: Object, followers: Object, tab: String })
const { add: toast } = useToast()

const localFollowers = ref(props.followers.data.map(u => ({ ...u })))

const toggleFollow = async (user) => {
  try {
    const { data } = await axios.post(route('profile.follow', user.id))
    user.is_following = data.following
    user.followers_count = data.followers_count
    toast(data.following ? 'Abonnement ajouté !' : 'Abonnement retiré.')
  } catch { toast('Erreur.', 'error') }
}

const goToPage = (page) => {
  const routeName = props.tab === 'followers' ? 'profile.followers' : 'profile.following'
  router.get(route(routeName, props.profileUser.id), { page })
}
</script>

<style scoped>
.followers-layout { max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem; }
.followers-header { display: flex; flex-direction: column; gap: .75rem; }
.back-link { color: var(--color-text-muted); text-decoration: none; font-size: .875rem; transition: color .2s; }
.back-link:hover { color: var(--color-accent); }
.tabs-bar { display: flex; gap: .25rem; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: .4rem; }
.tab-btn { flex: 1; padding: .6rem 1rem; border-radius: var(--radius-md); color: var(--color-text-muted); text-decoration: none; font-size: .875rem; font-weight: 500; text-align: center; transition: all .2s; }
.tab-btn:hover { background: var(--color-surface-2); color: var(--color-text); }
.tab-btn.active { background: var(--color-accent-soft); color: var(--color-accent); font-weight: 600; }
.users-list { display: flex; flex-direction: column; gap: .75rem; }
.user-card { padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.user-info { display: flex; align-items: center; gap: .75rem; text-decoration: none; flex: 1; }
.user-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.user-name { font-weight: 600; font-size: .9rem; color: var(--color-text); }
.user-username { font-size: .78rem; color: var(--color-text-muted); }
.user-stats { font-size: .72rem; color: var(--color-text-faint); margin-top: .1rem; }
.follow-btn-sm { padding: .35rem .9rem; font-size: .8rem; white-space: nowrap; }
.follow-btn-sm.following { background: var(--color-surface-2); color: var(--color-text-muted); }
.empty-state { padding: 2rem; text-align: center; color: var(--color-text-muted); }
.pagination { display: flex; justify-content: center; gap: .5rem; flex-wrap: wrap; }
.page-btn { width: 36px; height: 36px; border-radius: var(--radius-sm); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-muted); cursor: pointer; font-weight: 600; font-size: .875rem; transition: all .15s; }
.page-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.page-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
</style>
