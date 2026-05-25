<template>
  <AppLayout>
    <div class="profile-layout">
      <div class="profile-hero card">
        <div class="hero-bg" />
        <div class="hero-content">
          <div class="avatar-wrap">
            <img :src="profileUser.avatar_url" :alt="profileUser.name" class="hero-avatar" />
          </div>
          <div class="hero-info">
            <h1 class="font-display hero-name">{{ profileUser.name }}</h1>
            <p class="hero-username">@{{ profileUser.username }}</p>
            <p v-if="profileUser.bio" class="hero-bio">{{ profileUser.bio }}</p>
          </div>
          <div class="hero-actions">
            <template v-if="!profileUser.is_own_profile && $page.props.auth.user">
              <button
                class="btn-primary follow-btn"
                :class="{ following: isFollowing }"
                @click="toggleFollow"
                :disabled="followLoading"
              >
                {{ isFollowing ? '✓ Abonné' : '+ Suivre' }}
              </button>
            </template>
            <Link v-if="profileUser.is_own_profile" :href="route('profile.edit')" class="btn-ghost">✏️ Modifier</Link>
          </div>
        </div>

        <div class="stats-bar">
          <div class="stat-item">
            <span class="stat-num font-display">{{ stats.questions_created }}</span>
            <span class="stat-label">Questions</span>
          </div>
          <div class="stat-divider" />
          <div class="stat-item">
            <span class="stat-num font-display">{{ stats.total_votes_received }}</span>
            <span class="stat-label">Votes reçus</span>
          </div>
          <div class="stat-divider" />
          <Link :href="route('profile.followers', profileUser.id)" class="stat-item stat-link">
            <span class="stat-num font-display">{{ localFollowersCount }}</span>
            <span class="stat-label">Abonnés</span>
          </Link>
          <div class="stat-divider" />
          <Link :href="route('profile.following', profileUser.id)" class="stat-item stat-link">
            <span class="stat-num font-display">{{ profileUser.following_count }}</span>
            <span class="stat-label">Abonnements</span>
          </Link>
        </div>
      </div>

      <!-- Questions -->
      <div v-if="questions.length" class="questions-grid">
        <div v-for="q in questions" :key="q.id" class="mini-card card">
          <div class="mini-header">
            <span class="category-badge">{{ catEmoji(q.category) }} {{ q.category }}</span>
            <span class="mini-time">{{ q.created_at }}</span>
          </div>
          <div class="mini-options">
            <span class="mini-opt opt-a">{{ q.options[0]?.label }}</span>
            <span class="mini-vs font-display">VS</span>
            <span class="mini-opt opt-b">{{ q.options[1]?.label }}</span>
          </div>
          <div class="mini-footer">
            <span class="mini-stat">🗳️ {{ q.total_votes }}</span>
            <span class="mini-stat">❤️ {{ q.total_likes }}</span>
            <Link :href="route('questions.show', q.id)" class="mini-link">Voir →</Link>
          </div>
        </div>
      </div>
      <div v-else class="empty-state card">
        <p>Aucune question pour l'instant.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

const props = defineProps({ profileUser: Object, questions: Array, stats: Object })
const { add: toast } = useToast()

const isFollowing        = ref(props.profileUser.is_following)
const localFollowersCount = ref(props.profileUser.followers_count)
const followLoading      = ref(false)

const catEmoji = (c) => ({ amour:'❤️', aventure:'🗺️', nourriture:'🍕', technologie:'💻', voyage:'✈️', sport:'⚽', musique:'🎵', 'cinéma':'🎬', divers:'🎲' })[c] || '🎲'

const toggleFollow = async () => {
  if (followLoading.value) return
  followLoading.value = true
  try {
    const { data } = await axios.post(route('profile.follow', props.profileUser.id))
    isFollowing.value         = data.following
    localFollowersCount.value = data.followers_count
    toast(data.following ? 'Abonnement ajouté !' : 'Abonnement retiré.')
  } catch { toast('Erreur.', 'error') }
  finally { followLoading.value = false }
}
</script>

<style scoped>
.profile-layout { max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.profile-hero { padding: 0; overflow: hidden; position: relative; }
.hero-bg { position: absolute; top: 0; left: 0; right: 0; height: 120px; background: linear-gradient(135deg, var(--color-accent) 0%, #6366f1 100%); opacity: .15; }
.hero-content { position: relative; padding: 1.5rem 2rem 1rem; display: flex; align-items: flex-end; gap: 1.5rem; flex-wrap: wrap; }
.avatar-wrap { margin-top: 2rem; flex-shrink: 0; }
.hero-avatar { width: 96px; height: 96px; border-radius: 50%; object-fit: cover; border: 3px solid var(--color-surface); box-shadow: var(--shadow-md); }
.hero-info { flex: 1; }
.hero-name { font-size: 1.5rem; font-weight: 800; margin: 0 0 .2rem; }
.hero-username { color: var(--color-text-muted); font-size: .875rem; margin: 0 0 .4rem; }
.hero-bio { color: var(--color-text-muted); font-size: .875rem; margin: 0; line-height: 1.5; }
.hero-actions { display: flex; gap: .5rem; align-self: center; flex-wrap: wrap; }
.follow-btn { transition: all .2s; }
.follow-btn.following { background: var(--color-surface-2); color: var(--color-text-muted); border: 1px solid var(--color-border); }
.stats-bar { display: flex; border-top: 1px solid var(--color-border); padding: 1rem 2rem; gap: 0; }
.stat-item { flex: 1; display: flex; flex-direction: column; align-items: center; gap: .15rem; }
.stat-link { text-decoration: none; transition: color .2s; }
.stat-link:hover .stat-num { color: var(--color-accent); }
.stat-num { font-size: 1.4rem; font-weight: 800; color: var(--color-accent); }
.stat-label { font-size: .72rem; color: var(--color-text-muted); text-align: center; }
.stat-divider { width: 1px; background: var(--color-border); margin: 0 .5rem; }
.questions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
.mini-card { padding: 1.25rem; display: flex; flex-direction: column; gap: .75rem; }
.mini-header { display: flex; align-items: center; justify-content: space-between; }
.mini-time { font-size: .72rem; color: var(--color-text-faint); }
.mini-options { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.mini-opt { font-size: .85rem; font-weight: 500; padding: .3rem .7rem; border-radius: var(--radius-sm); flex: 1; text-align: center; min-width: 80px; }
.opt-a { background: rgba(99,102,241,.1); color: #6366f1; }
.opt-b { background: rgba(236,72,153,.1); color: #ec4899; }
.mini-vs { font-size: .7rem; font-weight: 900; color: var(--color-text-faint); flex-shrink: 0; }
.mini-footer { display: flex; align-items: center; gap: .75rem; font-size: .78rem; color: var(--color-text-muted); }
.mini-stat { display: flex; align-items: center; gap: .2rem; }
.mini-link { margin-left: auto; color: var(--color-accent); text-decoration: none; font-size: .8rem; font-weight: 600; }
.category-badge { background: var(--color-accent-soft); color: var(--color-accent); padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600; text-transform: capitalize; }
.empty-state { padding: 2rem; text-align: center; color: var(--color-text-muted); }
@media (max-width: 600px) { .hero-content { padding: 1.25rem; } .hero-avatar { width: 72px; height: 72px; } .stats-bar { padding: 1rem; } }
</style>
