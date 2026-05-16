<template>
  <AppLayout>
    <div class="index-layout">

      <!-- Sidebar gauche -->
      <aside class="sidebar-left">
        <div class="sidebar-card">
          <h3 class="sidebar-title font-display">Type</h3>
          <nav class="category-nav">
            <button class="cat-btn" :class="{ active: !filters.type }" @click="filterBy('type', null)">🎲 Tous</button>
            <button class="cat-btn" :class="{ active: filters.type === 'simple' }" @click="filterBy('type', 'simple')">⚡ Simple</button>
            <button class="cat-btn" :class="{ active: filters.type === 'group' }" @click="filterBy('type', 'group')">📦 Groupe</button>
            <button class="cat-btn" :class="{ active: filters.type === 'elimination' }" @click="filterBy('type', 'elimination')">🏆 Éliminatoire</button>
          </nav>
        </div>
        <div class="sidebar-card">
          <h3 class="sidebar-title font-display">Catégories</h3>
          <nav class="category-nav">
            <button class="cat-btn" :class="{ active: !filters.category }" @click="filterBy('category', null)">🎲 Toutes</button>
            <button v-for="cat in allCategories" :key="cat" class="cat-btn" :class="{ active: filters.category === cat }" @click="filterBy('category', cat)">{{ catEmoji(cat) }} {{ cat }}</button>
          </nav>
        </div>
        <div class="sidebar-card">
          <h3 class="sidebar-title font-display">Trier par</h3>
          <div class="sort-btns">
            <button v-for="s in sorts" :key="s.value" class="cat-btn" :class="{ active: (filters.sort || 'recent') === s.value }" @click="filterBy('sort', s.value)">{{ s.label }}</button>
          </div>
        </div>
      </aside>

      <!-- Feed principal -->
      <div class="feed">
        <div class="mobile-search-bar"><SearchBar /></div>

        <!-- Suggestions -->
        <div v-if="suggestions.length" class="suggestions-band">
          <div class="suggestions-header"><span class="font-display suggestions-title">✨ Pour toi</span></div>
          <div class="suggestions-scroll">
            <Link v-for="s in suggestions" :key="s.id" :href="route('questions.show', s.id)" class="suggestion-chip">
              {{ s.options[0]?.label }} vs {{ s.options[1]?.label }}
            </Link>
          </div>
        </div>

        <!-- Filtres mobile -->
        <div class="mobile-cats">
          <button class="cat-pill" :class="{ active: !filters.type }" @click="filterBy('type', null)">Tous</button>
          <button class="cat-pill" :class="{ active: filters.type === 'simple' }" @click="filterBy('type', 'simple')">⚡ Simple</button>
          <button class="cat-pill" :class="{ active: filters.type === 'group' }" @click="filterBy('type', 'group')">📦 Groupe</button>
          <button class="cat-pill" :class="{ active: filters.type === 'elimination' }" @click="filterBy('type', 'elimination')">🏆 Éliminatoire</button>
          <span class="cat-pill-sep">|</span>
          <button v-for="cat in allCategories" :key="cat" class="cat-pill" :class="{ active: filters.category === cat }" @click="filterBy('category', cat)">{{ catEmoji(cat) }} {{ cat }}</button>
        </div>

        <!-- Liste -->
        <div v-if="questions.data.length" class="questions-list">
          <template v-for="(item, i) in questions.data" :key="item.id + '-' + item.item_type">
            <!-- Question simple -->
            <QuestionCard v-if="item.item_type === 'question'" :question="item" :delay="i * 60" />

            <!-- Groupe ou éliminatoire -->
            <div v-else class="group-card card animate-fade-up" :style="{ animationDelay: i * 60 + 'ms' }">
              <div class="gc-header">
                <span class="gc-badge" :class="item.type === 'elimination' ? 'gc-elim' : 'gc-group'">
                  {{ item.type === 'elimination' ? '🏆 Éliminatoire' : '📦 Groupe' }}
                </span>
                <span class="gc-cat">{{ catEmoji(item.category) }} {{ item.category }}</span>
                <span class="gc-time">{{ item.created_at }}</span>
              </div>

              <h2 class="font-display gc-title">{{ item.title }}</h2>
              <p v-if="item.description" class="gc-desc">{{ item.description }}</p>

              <div class="gc-meta">
                <span v-if="item.type === 'group'">{{ item.total_questions }} questions</span>
                <span v-else>{{ item.total_items }} items</span>
                <span v-if="item.author" class="gc-author">
                  <img :src="item.author.avatar_url" class="gc-avatar" />{{ item.author.name }}
                </span>
              </div>

              <!-- Stats éliminatoire (top 3) -->
              <div v-if="item.type === 'elimination' && item.winner_stats?.length" class="gc-stats">
                <div v-for="(s, si) in item.winner_stats.slice(0,3)" :key="si" class="gc-stat-row">
                  <span class="gc-stat-rank font-display">{{ si + 1 }}</span>
                  <span class="gc-stat-label">{{ s.item?.label }}</span>
                  <div class="gc-stat-bar"><div class="gc-stat-fill" :style="{ width: s.percentage + '%', background: ['#f97316','#6366f1','#ec4899'][si] }" /></div>
                  <span class="gc-stat-pct font-display">{{ s.percentage }}%</span>
                </div>
              </div>

              <!-- Progression groupe -->
              <div v-if="item.type === 'group' && $page.props.auth.user && item.current_position > 0" class="gc-progress">
                <div class="gc-progress-bar"><div class="gc-progress-fill" :style="{ width: Math.round(item.current_position / item.total_questions * 100) + '%' }" /></div>
                <span class="gc-progress-label">{{ item.current_position }}/{{ item.total_questions }}</span>
              </div>

              <div class="gc-footer">
                <span v-if="item.completed" class="gc-done">✓ Terminé</span>
                <Link :href="route('groups.show', item.id)" class="btn-primary gc-btn">
                  {{ item.type === 'elimination' ? '⚔️ Jouer' : '▶ Commencer' }}
                </Link>
              </div>
            </div>
          </template>
        </div>

        <div v-else class="empty-state">
          <div class="empty-emoji">🤔</div>
          <h2 class="font-display">Aucune question trouvée</h2>
          <p>Sois le premier à en poser une !</p>
          <Link v-if="$page.props.auth.user" :href="route('groups.create')" class="btn-primary">Créer</Link>
        </div>

        <!-- Pagination -->
        <div v-if="questions.last_page > 1" class="pagination">
          <button v-for="page in questions.last_page" :key="page" class="page-btn" :class="{ active: page === questions.current_page }" @click="goToPage(page)">{{ page }}</button>
        </div>
      </div>

      <!-- Sidebar droite -->
      <aside class="sidebar-right">
        <div v-if="!$page.props.auth.user" class="sidebar-card cta-card">
          <div class="cta-emoji">🎮</div>
          <h3 class="font-display cta-title">Rejoins la communauté !</h3>
          <p class="cta-text">Connecte-toi pour voter, liker et créer tes propres questions.</p>
          <Link :href="route('register')" class="btn-primary" style="width:100%;justify-content:center;">S'inscrire</Link>
          <Link :href="route('login')" class="btn-ghost" style="width:100%;justify-content:center;margin-top:.5rem;">Se connecter</Link>
        </div>
        <div v-else class="sidebar-card cta-card">
          <div class="cta-emoji">✏️</div>
          <h3 class="font-display cta-title">Lance un dilemme !</h3>
          <p class="cta-text">Crée ta propre question, groupe ou tournoi éliminatoire.</p>
          <Link :href="route('groups.create')" class="btn-primary" style="width:100%;justify-content:center;">Créer</Link>
        </div>
        <div class="sidebar-card">
          <h3 class="sidebar-title font-display">Stats globales</h3>
          <div class="global-stats">
            <div class="gstat"><span class="gstat-num font-display">{{ questions.total }}</span><span class="gstat-label">Contenus</span></div>
          </div>
        </div>
      </aside>

    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import QuestionCard from '@/Components/QuestionCard.vue'
import SearchBar from '@/Components/SearchBar.vue'

const props = defineProps({ questions: Object, suggestions: Array, filters: Object, categories: Array })

const allCategories = ['amour','aventure','nourriture','technologie','voyage','sport','musique','cinéma','divers']
const sorts = [
  { value: 'recent',  label: '🕐 Récentes' },
  { value: 'popular', label: '❤️ Populaires' },
  { value: 'votes',   label: '🔥 Plus votées' },
]

const catEmoji = (c) => ({ amour:'❤️', aventure:'🗺️', nourriture:'🍕', technologie:'💻', voyage:'✈️', sport:'⚽', musique:'🎵', 'cinéma':'🎬', divers:'🎲' })[c] || '🎲'

const filterBy = (key, value) => {
  router.get(route('questions.index'), { ...props.filters, [key]: value || undefined }, { preserveState: true, replace: true })
}

const goToPage = (page) => {
  router.get(route('questions.index'), { ...props.filters, page }, { preserveScroll: false })
}
</script>

<style scoped>
.index-layout { display: grid; grid-template-columns: 220px 1fr 260px; gap: 2rem; align-items: start; }
.sidebar-left, .sidebar-right { position: sticky; top: 80px; display: flex; flex-direction: column; gap: 1rem; }
.sidebar-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 1.25rem; }
.sidebar-title { font-size: .8rem; font-weight: 800; letter-spacing: .06em; color: var(--color-text-muted); text-transform: uppercase; margin: 0 0 .75rem; }
.category-nav, .sort-btns { display: flex; flex-direction: column; gap: .25rem; }
.cat-btn { text-align: left; padding: .45rem .75rem; border-radius: var(--radius-sm); border: none; background: none; color: var(--color-text-muted); font-size: .875rem; cursor: pointer; transition: all .15s; text-transform: capitalize; font-family: var(--font-body); }
.cat-btn:hover { background: var(--color-surface-2); color: var(--color-text); }
.cat-btn.active { background: var(--color-accent-soft); color: var(--color-accent); font-weight: 600; }
.cta-card { text-align: center; }
.cta-emoji { font-size: 2rem; margin-bottom: .5rem; }
.cta-title { font-size: 1rem; font-weight: 700; margin: 0 0 .4rem; }
.cta-text { font-size: .8rem; color: var(--color-text-muted); margin: 0 0 1rem; line-height: 1.5; }
.global-stats { display: flex; gap: 1rem; }
.gstat { display: flex; flex-direction: column; align-items: center; gap: .1rem; }
.gstat-num { font-size: 1.5rem; font-weight: 800; color: var(--color-accent); }
.gstat-label { font-size: .7rem; color: var(--color-text-muted); }
.feed { min-width: 0; }
.questions-list { display: flex; flex-direction: column; gap: 1.25rem; }
.suggestions-band { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 1rem 1.25rem; margin-bottom: 1.25rem; }
.suggestions-header { margin-bottom: .6rem; }
.suggestions-title { font-size: .8rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .05em; }
.suggestions-scroll { display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .25rem; }
.suggestions-scroll::-webkit-scrollbar { display: none; }
.suggestion-chip { flex-shrink: 0; padding: .4rem .9rem; background: var(--color-surface-2); border: 1px solid var(--color-border); border-radius: 999px; font-size: .78rem; color: var(--color-text-muted); text-decoration: none; white-space: nowrap; max-width: 200px; overflow: hidden; text-overflow: ellipsis; transition: all .2s; }
.suggestion-chip:hover { border-color: var(--color-accent); color: var(--color-accent); }
.mobile-search-bar { display: none; margin-bottom: 1rem; }
.mobile-cats { display: none; gap: .5rem; overflow-x: auto; padding-bottom: .25rem; margin-bottom: 1rem; }
.mobile-cats::-webkit-scrollbar { display: none; }
.cat-pill { flex-shrink: 0; padding: .35rem .8rem; border: 1px solid var(--color-border); border-radius: 999px; background: none; color: var(--color-text-muted); font-size: .78rem; cursor: pointer; transition: all .15s; font-family: var(--font-body); text-transform: capitalize; }
.cat-pill:hover { border-color: var(--color-accent); color: var(--color-accent); }
.cat-pill.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
.cat-pill-sep { color: var(--color-border); flex-shrink: 0; line-height: 2; }

/* Group card */
.group-card { padding: 1.5rem; display: flex; flex-direction: column; gap: .75rem; }
.gc-header { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.gc-badge { font-size: .75rem; font-weight: 600; padding: .2rem .7rem; border-radius: 999px; }
.gc-group { background: rgba(99,102,241,.15); color: #6366f1; }
.gc-elim  { background: rgba(245,158,11,.15); color: #f59e0b; }
.gc-cat { font-size: .75rem; color: var(--color-text-muted); text-transform: capitalize; }
.gc-time { font-size: .72rem; color: var(--color-text-faint); margin-left: auto; }
.gc-title { font-size: 1.1rem; font-weight: 700; margin: 0; line-height: 1.4; }
.gc-desc { font-size: .825rem; color: var(--color-text-muted); margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.gc-meta { display: flex; align-items: center; gap: .75rem; font-size: .78rem; color: var(--color-text-muted); }
.gc-author { display: flex; align-items: center; gap: .3rem; }
.gc-avatar { width: 18px; height: 18px; border-radius: 50%; object-fit: cover; }
.gc-stats { display: flex; flex-direction: column; gap: .4rem; }
.gc-stat-row { display: flex; align-items: center; gap: .5rem; }
.gc-stat-rank { width: 18px; font-size: .75rem; font-weight: 800; color: var(--color-text-muted); flex-shrink: 0; }
.gc-stat-label { font-size: .8rem; font-weight: 500; width: 80px; flex-shrink: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gc-stat-bar { flex: 1; height: 6px; background: var(--color-border); border-radius: 3px; overflow: hidden; }
.gc-stat-fill { height: 100%; border-radius: 3px; transition: width .8s ease; }
.gc-stat-pct { font-size: .8rem; font-weight: 800; width: 34px; text-align: right; flex-shrink: 0; }
.gc-progress { display: flex; align-items: center; gap: .5rem; }
.gc-progress-bar { flex: 1; height: 5px; background: var(--color-border); border-radius: 3px; overflow: hidden; }
.gc-progress-fill { height: 100%; background: var(--color-accent); border-radius: 3px; transition: width .4s ease; }
.gc-progress-label { font-size: .72rem; color: var(--color-text-muted); white-space: nowrap; }
.gc-footer { display: flex; align-items: center; justify-content: space-between; padding-top: .5rem; border-top: 1px solid var(--color-border); }
.gc-done { font-size: .8rem; color: #22c55e; font-weight: 600; }
.gc-btn { padding: .45rem 1.1rem; font-size: .85rem; }

.empty-state { text-align: center; padding: 4rem 1rem; display: flex; flex-direction: column; align-items: center; gap: 1rem; }
.empty-emoji { font-size: 3rem; }
.empty-state h2 { font-size: 1.25rem; margin: 0; }
.empty-state p { color: var(--color-text-muted); margin: 0; }
.pagination { display: flex; justify-content: center; gap: .5rem; margin-top: 2rem; flex-wrap: wrap; }
.page-btn { width: 36px; height: 36px; border-radius: var(--radius-sm); border: 1px solid var(--color-border); background: var(--color-surface); color: var(--color-text-muted); cursor: pointer; font-family: var(--font-display); font-weight: 600; font-size: .875rem; transition: all .15s; }
.page-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.page-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }

@media (max-width: 1100px) { .index-layout { grid-template-columns: 200px 1fr; } .sidebar-right { display: none; } }
@media (max-width: 768px) { .index-layout { grid-template-columns: 1fr; } .sidebar-left { display: none; } .mobile-search-bar { display: block; } .mobile-cats { display: flex; } }
</style>
