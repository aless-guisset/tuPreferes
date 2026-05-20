<template>
  <AppLayout>
    <div class="profile-layout">

      <!-- ── Profile header ─────────────────────────────────────────────── -->
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
            <Link :href="route('profile.edit')" class="btn-ghost">✏️ Modifier</Link>
            <form @submit.prevent="logout">
              <button type="submit" class="btn-ghost">{{ t("nav.logout") }}</button>
            </form>
          </div>
        </div>

        <!-- Stats bar -->
        <div class="stats-bar">
          <div class="stat-item">
            <span class="stat-num font-display">{{ stats.questions_created }}</span>
            <span class="stat-label">{{ t("profile.questions_created") }}</span>
          </div>
          <div class="stat-divider" />
          <div class="stat-item">
            <span class="stat-num font-display">{{ stats.questions_answered }}</span>
            <span class="stat-label">{{ t("profile.questions_answered") }}</span>
          </div>
          <div class="stat-divider" />
          <div class="stat-item">
            <span class="stat-num font-display">{{ stats.total_votes_received }}</span>
            <span class="stat-label">{{ t("profile.votes_received") }}</span>
          </div>
        </div>
      </div>

      <!-- ── Tabs ───────────────────────────────────────────────────────── -->
      <div class="tabs-bar">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          class="tab-btn"
          :class="{ active: activeTab === tab.id }"
          @click="activeTab = tab.id"
        >{{ tab.icon }} {{ tab.label }}</button>
      </div>

      <!-- ── Tab content ────────────────────────────────────────────────── -->

      <!-- Mes questions -->
      <div v-if="activeTab === 'questions'" class="tab-content">
        <div v-if="myQuestions.length" class="questions-grid">
          <div v-for="q in myQuestions" :key="q.id" class="mini-card card">
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
              <span class="mini-stat">🗳️ {{ q.total_votes }} votes</span>
              <span class="mini-stat">❤️ {{ q.total_likes }} likes</span>
              <Link :href="route('questions.show', q.id)" class="mini-link">Voir →</Link>
            </div>
          </div>
        </div>
        <div v-else class="empty-tab">
          <p>Tu n'as pas encore créé de question.</p>
          <Link :href="route('questions.create')" class="btn-primary">Créer une question</Link>
        </div>
      </div>

      <!-- Historique -->
      <div v-if="activeTab === 'history'" class="tab-content" id="history">
        <div v-if="history.length" class="history-list">
          <div v-for="h in history" :key="h.id" class="history-item card">
            <div class="history-opts">
              <span class="hist-opt">{{ h.options[0]?.label }}</span>
              <span class="hist-vs font-display">VS</span>
              <span class="hist-opt">{{ h.options[1]?.label }}</span>
            </div>
            <div class="history-meta">
              <span class="category-badge">{{ catEmoji(h.category) }} {{ h.category }}</span>
              <span class="hist-time">Vu {{ h.viewed_at }}</span>
              <Link :href="route('questions.show', h.id)" class="mini-link">Répondre →</Link>
            </div>
          </div>
        </div>
        <div v-else class="empty-tab">
          <p>Ton historique est vide pour l'instant.</p>
        </div>
      </div>

      <!-- Mes votes -->
      <div v-if="activeTab === 'votes'" class="tab-content">
        <div v-if="myVotes.length" class="votes-list">
          <div v-for="v in myVotes" :key="v.question_id" class="vote-item card">
            <div class="vote-info">
              <span class="category-badge">{{ catEmoji(v.category) }} {{ v.category }}</span>
              <span class="vote-time">{{ v.voted_at }}</span>
            </div>
            <p class="vote-chosen">
              {{ t("profile.chosen") }} <strong>{{ v.chosen_option }}</strong>
            </p>
            <Link :href="route('questions.show', v.question_id)" class="mini-link">{{ t("profile.see_results") }}</Link>
          </div>
        </div>
        <div v-else class="empty-tab">
          <p>Tu n'as pas encore répondu à des questions.</p>
          <Link :href="route('questions.index')" class="btn-primary">{{ t("profile.explore") }}</Link>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from '@/Composables/useI18n'
const { t } = useI18n()
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
  profileUser: Object,
  myQuestions: Array,
  history: Array,
  myVotes: Array,
  stats: Object,
})

const activeTab = ref('questions')
const tabs = [
  { id: 'questions', label: t('profile.my_questions'), icon: '🎯' },
  { id: 'history',   label: t('profile.history'), icon: '🕐' },
  { id: 'votes',     label: t('profile.my_votes'), icon: '✅' },
]

const catEmoji = (c) => ({
  amour:'❤️', aventure:'🗺️', nourriture:'🍕', technologie:'💻',
  voyage:'✈️', sport:'⚽', musique:'🎵', 'cinéma':'🎬', divers:'🎲'
})[c] || '🎲'

const logout = () => router.post(route('logout'))
</script>

<style scoped>
.profile-layout { max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }

/* Hero */
.profile-hero { padding: 0; overflow: hidden; position: relative; }
.hero-bg {
  position: absolute; top: 0; left: 0; right: 0; height: 120px;
  background: linear-gradient(135deg, var(--color-accent) 0%, #6366f1 100%);
  opacity: .15;
}
.hero-content {
  position: relative;
  padding: 1.5rem 2rem 1rem;
  display: flex;
  align-items: flex-end;
  gap: 1.5rem;
  flex-wrap: wrap;
}
.avatar-wrap { margin-top: 2rem; flex-shrink: 0; }
.hero-avatar { width: 96px; height: 96px; border-radius: 50%; object-fit: cover; border: 3px solid var(--color-surface); box-shadow: var(--shadow-md); }
.hero-info { flex: 1; }
.hero-name { font-size: 1.5rem; font-weight: 800; margin: 0 0 .2rem; }
.hero-username { color: var(--color-text-muted); font-size: .875rem; margin: 0 0 .4rem; }
.hero-bio { color: var(--color-text-muted); font-size: .875rem; margin: 0; line-height: 1.5; }
.hero-actions { display: flex; gap: .5rem; align-self: center; flex-wrap: wrap; }

.stats-bar {
  display: flex;
  border-top: 1px solid var(--color-border);
  padding: 1rem 2rem;
  gap: 0;
}
.stat-item { flex: 1; display: flex; flex-direction: column; align-items: center; gap: .15rem; }
.stat-num { font-size: 1.4rem; font-weight: 800; color: var(--color-accent); }
.stat-label { font-size: .72rem; color: var(--color-text-muted); text-align: center; }
.stat-divider { width: 1px; background: var(--color-border); margin: 0 .5rem; }

/* Tabs */
.tabs-bar {
  display: flex;
  gap: .25rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: .4rem;
}
.tab-btn {
  flex: 1;
  padding: .6rem 1rem;
  border: none;
  border-radius: var(--radius-md);
  background: none;
  color: var(--color-text-muted);
  font-family: var(--font-body);
  font-size: .875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all .2s;
}
.tab-btn:hover { background: var(--color-surface-2); color: var(--color-text); }
.tab-btn.active { background: var(--color-accent-soft); color: var(--color-accent); font-weight: 600; }

/* Questions grid */
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
.mini-link:hover { text-decoration: underline; }

/* History */
.history-list { display: flex; flex-direction: column; gap: .75rem; }
.history-item { padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.history-opts { flex: 1; display: flex; align-items: center; gap: .5rem; min-width: 200px; }
.hist-opt { font-size: .875rem; font-weight: 500; flex: 1; }
.hist-vs { font-size: .65rem; font-weight: 900; color: var(--color-text-faint); flex-shrink: 0; }
.history-meta { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.hist-time { font-size: .75rem; color: var(--color-text-faint); }

/* Votes */
.votes-list { display: flex; flex-direction: column; gap: .75rem; }
.vote-item { padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: .5rem; }
.vote-info { display: flex; align-items: center; gap: .75rem; }
.vote-time { font-size: .75rem; color: var(--color-text-faint); }
.vote-chosen { font-size: .9rem; color: var(--color-text-muted); margin: 0; }
.vote-chosen strong { color: var(--color-text); }

/* Category badge */
.category-badge {
  background: var(--color-accent-soft);
  color: var(--color-accent);
  padding: .2rem .6rem;
  border-radius: 999px;
  font-size: .72rem;
  font-weight: 600;
  text-transform: capitalize;
}

/* Empty */
.empty-tab { text-align: center; padding: 3rem 1rem; color: var(--color-text-muted); display: flex; flex-direction: column; align-items: center; gap: 1rem; }

/* Responsive */
@media (max-width: 600px) {
  .hero-content { padding: 1.25rem; gap: 1rem; }
  .hero-avatar { width: 72px; height: 72px; }
  .stats-bar { padding: 1rem; }
  .tabs-bar { overflow-x: auto; }
  .tab-btn { white-space: nowrap; }
}
</style>
