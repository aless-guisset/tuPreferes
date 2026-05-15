<template>
  <AppLayout>
    <div class="index-layout">

      <!-- ── Left sidebar (desktop) ──────────────────────────────────────── -->
      <aside class="sidebar-left">
        <div class="sidebar-card">
          <h3 class="sidebar-title font-display">Catégories</h3>
          <nav class="category-nav">
            <button
              class="cat-btn"
              :class="{ active: !filters.category }"
              @click="filterBy('category', null)"
            >🎲 Toutes</button>
            <button
              v-for="cat in allCategories"
              :key="cat"
              class="cat-btn"
              :class="{ active: filters.category === cat }"
              @click="filterBy('category', cat)"
            >{{ catEmoji(cat) }} {{ cat }}</button>
          </nav>
        </div>

        <!-- Sort -->
        <div class="sidebar-card">
          <h3 class="sidebar-title font-display">Trier par</h3>
          <div class="sort-btns">
            <button
              v-for="s in sorts"
              :key="s.value"
              class="cat-btn"
              :class="{ active: (filters.sort || 'recent') === s.value }"
              @click="filterBy('sort', s.value)"
            >{{ s.label }}</button>
          </div>
        </div>
      </aside>

      <!-- ── Main feed ────────────────────────────────────────────────────── -->
      <div class="feed">

        <!-- Mobile search -->
        <div class="mobile-search-bar">
          <SearchBar />
        </div>

        <!-- Suggestions band (connecté) -->
        <div v-if="suggestions.length" class="suggestions-band">
          <div class="suggestions-header">
            <span class="font-display suggestions-title">✨ Pour toi</span>
          </div>
          <div class="suggestions-scroll">
            <Link
              v-for="s in suggestions"
              :key="s.id"
              :href="route('questions.show', s.id)"
              class="suggestion-chip"
            >
              {{ s.options[0]?.label }} vs {{ s.options[1]?.label }}
            </Link>
          </div>
        </div>

        <!-- Mobile category pills -->
        <div class="mobile-cats">
          <button
            class="cat-pill"
            :class="{ active: !filters.category }"
            @click="filterBy('category', null)"
          >Toutes</button>
          <button
            v-for="cat in allCategories"
            :key="cat"
            class="cat-pill"
            :class="{ active: filters.category === cat }"
            @click="filterBy('category', cat)"
          >{{ catEmoji(cat) }} {{ cat }}</button>
        </div>

        <!-- Questions list -->
        <div v-if="questions.data.length" class="questions-list">
          <QuestionCard
            v-for="(q, i) in questions.data"
            :key="q.id"
            :question="q"
            :delay="i * 60"
          />
        </div>

        <!-- Empty state -->
        <div v-else class="empty-state">
          <div class="empty-emoji">🤔</div>
          <h2 class="font-display">Aucune question trouvée</h2>
          <p>Sois le premier à en poser une !</p>
          <Link v-if="$page.props.auth.user" :href="route('questions.create')" class="btn-primary">
            Créer une question
          </Link>
        </div>

        <!-- Pagination -->
        <div v-if="questions.last_page > 1" class="pagination">
          <button
            v-for="page in questions.last_page"
            :key="page"
            class="page-btn"
            :class="{ active: page === questions.current_page }"
            @click="goToPage(page)"
          >{{ page }}</button>
        </div>
      </div>

      <!-- ── Right sidebar (desktop) ─────────────────────────────────────── -->
      <aside class="sidebar-right">
        <!-- CTA connexion ou créer -->
        <div v-if="!$page.props.auth.user" class="sidebar-card cta-card">
          <div class="cta-emoji">🎮</div>
          <h3 class="font-display cta-title">Rejoins la communauté !</h3>
          <p class="cta-text">Connecte-toi pour voter, liker et créer tes propres questions.</p>
          <Link :href="route('register')" class="btn-primary" style="width:100%; justify-content:center;">
            S'inscrire gratuitement
          </Link>
          <Link :href="route('login')" class="btn-ghost" style="width:100%; justify-content:center; margin-top:.5rem;">
            Se connecter
          </Link>
        </div>

        <div v-else class="sidebar-card cta-card">
          <div class="cta-emoji">✏️</div>
          <h3 class="font-display cta-title">Lance un dilemme !</h3>
          <p class="cta-text">Crée ta propre question et vois ce que les autres choisissent.</p>
          <Link :href="route('questions.create')" class="btn-primary" style="width:100%; justify-content:center;">
            Créer une question
          </Link>
        </div>

        <!-- Stats -->
        <div class="sidebar-card">
          <h3 class="sidebar-title font-display">Stats globales</h3>
          <div class="global-stats">
            <div class="gstat">
              <span class="gstat-num font-display">{{ questions.total }}</span>
              <span class="gstat-label">Questions</span>
            </div>
          </div>
        </div>
      </aside>

    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import QuestionCard from '@/Components/QuestionCard.vue'
import SearchBar from '@/Components/SearchBar.vue'

const props = defineProps({
  questions: Object,
  suggestions: Array,
  filters: Object,
  categories: Array,
})

const allCategories = ['amour','aventure','nourriture','technologie','voyage','sport','musique','cinéma','divers']
const sorts = [
  { value: 'recent',  label: '🕐 Récentes' },
  { value: 'popular', label: '❤️ Populaires' },
  { value: 'votes',   label: '🔥 Plus votées' },
]

const catEmoji = (c) => ({
  amour:'❤️', aventure:'🗺️', nourriture:'🍕', technologie:'💻',
  voyage:'✈️', sport:'⚽', musique:'🎵', 'cinéma':'🎬', divers:'🎲'
})[c] || '🎲'

const filterBy = (key, value) => {
  router.get(route('questions.index'), {
    ...props.filters,
    [key]: value || undefined,
  }, { preserveState: true, replace: true })
}

const goToPage = (page) => {
  router.get(route('questions.index'), { ...props.filters, page }, { preserveScroll: false })
}
</script>

<style scoped>
.index-layout {
  display: grid;
  grid-template-columns: 220px 1fr 260px;
  gap: 2rem;
  align-items: start;
}

/* Sidebars */
.sidebar-left, .sidebar-right {
  position: sticky;
  top: 80px;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.sidebar-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: 1.25rem;
}
.sidebar-title {
  font-size: .8rem;
  font-weight: 800;
  letter-spacing: .06em;
  color: var(--color-text-muted);
  text-transform: uppercase;
  margin: 0 0 .75rem;
}
.category-nav, .sort-btns { display: flex; flex-direction: column; gap: .25rem; }
.cat-btn {
  text-align: left;
  padding: .45rem .75rem;
  border-radius: var(--radius-sm);
  border: none;
  background: none;
  color: var(--color-text-muted);
  font-size: .875rem;
  cursor: pointer;
  transition: all .15s;
  text-transform: capitalize;
  font-family: var(--font-body);
}
.cat-btn:hover { background: var(--color-surface-2); color: var(--color-text); }
.cat-btn.active { background: var(--color-accent-soft); color: var(--color-accent); font-weight: 600; }

/* CTA */
.cta-card { text-align: center; }
.cta-emoji { font-size: 2rem; margin-bottom: .5rem; }
.cta-title { font-size: 1rem; font-weight: 700; margin: 0 0 .4rem; }
.cta-text { font-size: .8rem; color: var(--color-text-muted); margin: 0 0 1rem; line-height: 1.5; }

/* Global stats */
.global-stats { display: flex; gap: 1rem; }
.gstat { display: flex; flex-direction: column; align-items: center; gap: .1rem; }
.gstat-num { font-size: 1.5rem; font-weight: 800; color: var(--color-accent); }
.gstat-label { font-size: .7rem; color: var(--color-text-muted); }

/* Feed */
.feed { min-width: 0; }
.questions-list { display: flex; flex-direction: column; gap: 1.25rem; }

/* Suggestions band */
.suggestions-band {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: 1rem 1.25rem;
  margin-bottom: 1.25rem;
}
.suggestions-header { margin-bottom: .6rem; }
.suggestions-title { font-size: .8rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .05em; }
.suggestions-scroll { display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .25rem; }
.suggestions-scroll::-webkit-scrollbar { display: none; }
.suggestion-chip {
  flex-shrink: 0;
  padding: .4rem .9rem;
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: 999px;
  font-size: .78rem;
  color: var(--color-text-muted);
  text-decoration: none;
  white-space: nowrap;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: all .2s;
}
.suggestion-chip:hover { border-color: var(--color-accent); color: var(--color-accent); }

/* Mobile search (hidden on desktop) */
.mobile-search-bar { display: none; margin-bottom: 1rem; }
.mobile-cats { display: none; gap: .5rem; overflow-x: auto; padding-bottom: .25rem; margin-bottom: 1rem; }
.mobile-cats::-webkit-scrollbar { display: none; }
.cat-pill {
  flex-shrink: 0;
  padding: .35rem .8rem;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  background: none;
  color: var(--color-text-muted);
  font-size: .78rem;
  cursor: pointer;
  transition: all .15s;
  font-family: var(--font-body);
  text-transform: capitalize;
}
.cat-pill:hover { border-color: var(--color-accent); color: var(--color-accent); }
.cat-pill.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }

/* Empty */
.empty-state { text-align: center; padding: 4rem 1rem; }
.empty-emoji { font-size: 3rem; margin-bottom: 1rem; }
.empty-state h2 { margin: 0 0 .5rem; font-size: 1.25rem; }
.empty-state p { color: var(--color-text-muted); margin: 0 0 1.5rem; }

/* Pagination */
.pagination { display: flex; justify-content: center; gap: .5rem; margin-top: 2rem; flex-wrap: wrap; }
.page-btn {
  width: 36px; height: 36px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text-muted);
  cursor: pointer;
  font-family: var(--font-display);
  font-weight: 600;
  font-size: .875rem;
  transition: all .15s;
}
.page-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.page-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }

/* Responsive */
@media (max-width: 1100px) {
  .index-layout { grid-template-columns: 200px 1fr; }
  .sidebar-right { display: none; }
}
@media (max-width: 768px) {
  .index-layout { grid-template-columns: 1fr; }
  .sidebar-left { display: none; }
  .mobile-search-bar { display: block; }
  .mobile-cats { display: flex; }
}
</style>
