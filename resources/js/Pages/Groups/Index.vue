<template>
  <AppLayout>
    <div class="groups-layout">
      <div class="page-header">
        <div>
          <h1 class="font-display page-title">Groupes & Tournois</h1>
          <p class="page-sub">Des séries de questions et des tournois éliminatoires</p>
        </div>
        <Link v-if="$page.props.auth.user" :href="route('groups.create')" class="btn-primary">+ Créer</Link>
      </div>
      <div class="type-filters">
        <button class="type-filter" :class="{ active: filter === 'all' }" @click="filter = 'all'">Tous</button>
        <button class="type-filter" :class="{ active: filter === 'group' }" @click="filter = 'group'">📦 Groupes</button>
        <button class="type-filter" :class="{ active: filter === 'elimination' }" @click="filter = 'elimination'">🏆 Tournois</button>
      </div>
      <div v-if="filteredGroups.length" class="groups-list">
        <div v-for="(g, i) in filteredGroups" :key="g.id" class="group-card card animate-fade-up" :style="{ animationDelay: i * 60 + 'ms' }">
          <div class="gc-header">
            <span class="gc-badge" :class="'gc-badge-' + g.type">{{ g.type === 'elimination' ? '🏆 Tournoi éliminatoire' : '📦 Groupe de questions' }}</span>
            <span class="gc-time">{{ g.created_at }}</span>
          </div>
          <h2 class="font-display gc-title">{{ g.title }}</h2>
          <p v-if="g.description" class="gc-desc">{{ g.description }}</p>
          <div class="gc-meta">
            <span>{{ g.total_questions }} {{ g.type === 'elimination' ? 'items' : 'questions' }}</span>
            <span v-if="g.author" class="gc-author"><img :src="g.author.avatar_url" class="gc-avatar" />{{ g.author.name }}</span>
          </div>
          <div v-if="g.type === 'group' && $page.props.auth.user && g.current_position > 0" class="gc-progress">
            <div class="gc-progress-bar"><div class="gc-progress-fill" :style="{ width: Math.round(g.current_position / g.total_questions * 100) + '%' }" /></div>
            <span class="gc-progress-label">{{ g.current_position }}/{{ g.total_questions }}</span>
          </div>
          <div class="gc-footer">
            <span v-if="g.completed" class="gc-done">✓ Terminé</span>
            <Link :href="route('groups.show', g.id)" class="btn-primary gc-btn">{{ g.type === 'elimination' ? '⚔️ Jouer' : '▶ Commencer' }}</Link>
          </div>
        </div>
      </div>
      <div v-else class="empty-state">
        <div class="empty-emoji">📦</div>
        <h2 class="font-display">Aucun groupe pour l'instant</h2>
        <p>Sois le premier à créer un groupe ou un tournoi !</p>
        <Link v-if="$page.props.auth.user" :href="route('groups.create')" class="btn-primary">Créer</Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ groups: Object })
const filter = ref('all')
const filteredGroups = computed(() => {
  const data = props.groups.data ?? []
  if (filter.value === 'all') return data
  return data.filter(g => g.type === filter.value)
})
</script>

<style scoped>
.groups-layout { max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.page-title { font-size: 1.75rem; font-weight: 800; margin: 0 0 .3rem; }
.page-sub { color: var(--color-text-muted); font-size: .9rem; margin: 0; }
.type-filters { display: flex; gap: .5rem; flex-wrap: wrap; }
.type-filter { padding: .4rem .9rem; border: 1px solid var(--color-border); border-radius: 999px; background: none; color: var(--color-text-muted); font-size: .825rem; cursor: pointer; transition: all .15s; font-family: var(--font-body); }
.type-filter:hover { border-color: var(--color-accent); color: var(--color-accent); }
.type-filter.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
.groups-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.25rem; }
.group-card { padding: 1.5rem; display: flex; flex-direction: column; gap: .75rem; }
.gc-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .4rem; }
.gc-badge { font-size: .75rem; font-weight: 600; padding: .2rem .7rem; border-radius: 999px; }
.gc-badge-group { background: rgba(99,102,241,.15); color: #6366f1; }
.gc-badge-elimination { background: rgba(245,158,11,.15); color: #f59e0b; }
.gc-time { font-size: .72rem; color: var(--color-text-faint); }
.gc-title { font-size: 1.05rem; font-weight: 700; margin: 0; line-height: 1.4; }
.gc-desc { font-size: .825rem; color: var(--color-text-muted); margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.gc-meta { display: flex; align-items: center; gap: .75rem; font-size: .78rem; color: var(--color-text-muted); }
.gc-author { display: flex; align-items: center; gap: .3rem; }
.gc-avatar { width: 18px; height: 18px; border-radius: 50%; object-fit: cover; }
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
</style>
