<template>
  <AppLayout>
    <div class="elim-layout">
      <div class="elim-hero card">
        <div class="elim-meta">
          <span class="elim-badge">🏆 Éliminatoire</span>
          <span class="elim-time">{{ group.created_at }}</span>
        </div>
        <h1 class="font-display elim-title">{{ group.title }}</h1>
        <p v-if="group.description" class="elim-desc">{{ group.description }}</p>
      </div>

      <!-- Non connecté -->
      <div v-if="!$page.props.auth.user" class="auth-prompt card">
        <div class="auth-icon">🔒</div>
        <h2 class="font-display">{{ t("elimination.start_title") }} !</h2>
        <p>Il faut un compte pour participer au tournoi.</p>
        <div class="auth-btns">
          <Link :href="route('login')" class="btn-primary">{{ t('nav.login') }}</Link>
          <Link :href="route('register')" class="btn-ghost">{{ t('nav.register') }}</Link>
        </div>
      </div>

      <!-- Pas encore commencé -->
      <div v-else-if="!localSession" class="start-screen card">
        <div class="start-icon">⚔️</div>
        <h2 class="font-display">{{ t("elimination.start_title") }} ?</h2>
        <p>{{ items.length }} items vont s'affronter en duels. Le dernier restant sera ton champion !</p>
        <div class="items-preview">
          <span v-for="item in items.slice(0, 8)" :key="item.id" class="item-chip">
            <img v-if="item.image" :src="item.image" class="item-chip-img" />
            {{ item.label }}
          </span>
          <span v-if="items.length > 8" class="item-chip item-chip-more">+{{ items.length - 8 }}</span>
        </div>
        <button class="btn-primary start-btn" @click="startTournament" :disabled="starting">
          {{ starting ? '⏳ ' + t('common.loading') : t('elimination.start_btn') }}
        </button>
      </div>

      <!-- Tournoi en cours -->
      <div v-else-if="!localSession.completed" class="duel-screen">
        <div class="duel-progress card">
          <div class="duel-progress-info">
            <span class="font-display duel-remaining">{{ tp('elimination.remaining', localSession.remaining_count) }}</span>
            <span class="duel-hint">{{ t("elimination.hint") }} !</span>
          </div>
          <div class="duel-progress-bar">
            <div class="duel-progress-fill" :style="{ width: eliminationPct + '%' }" />
          </div>
        </div>

        <Transition name="duel" mode="out-in">
          <div v-if="currentDuel" :key="duelKey" class="duel-cards">
            <button class="duel-card duel-a" @click="choose(currentDuel.a.id)" :disabled="choosing">
              <img v-if="currentDuel.a.image" :src="currentDuel.a.image" class="duel-img" />
              <span class="duel-label font-display">{{ currentDuel.a.label }}</span>
            </button>
            <div class="duel-vs"><span class="font-display">VS</span></div>
            <button class="duel-card duel-b" @click="choose(currentDuel.b.id)" :disabled="choosing">
              <img v-if="currentDuel.b.image" :src="currentDuel.b.image" class="duel-img" />
              <span class="duel-label font-display">{{ currentDuel.b.label }}</span>
            </button>
          </div>
        </Transition>
      </div>

      <!-- Résultat final -->
      <div v-else class="result-screen">
        <div class="winner-card card">
          <div class="winner-crown">👑</div>
          <h2 class="font-display winner-title">{{ t("elimination.champion") }} !</h2>
          <div class="winner-item">
            <img v-if="localSession.winner?.image" :src="localSession.winner.image" class="winner-img" />
            <span class="font-display winner-label">{{ localSession.winner?.label }}</span>
          </div>
          <button class="btn-ghost restart-btn" @click="restartTournament">🔄 {{ t("elimination.replay") }}</button>
        </div>

        <div v-if="localStats?.length" class="global-stats card">
          <h3 class="font-display stats-title">{{ t("elimination.community") }}</h3>
          <p class="stats-subtitle">{{ tp('elimination.players', totalSessions) }}</p>
          <div class="stats-list">
            <div v-for="(s, i) in localStats" :key="i" class="stat-row">
              <span class="stat-rank font-display">{{ i + 1 }}</span>
              <img v-if="s.item?.image_url" :src="s.item.image_url" class="stat-img" />
              <span class="stat-label">{{ s.item?.label ?? '?' }}</span>
              <div class="stat-bar-wrap">
                <div class="stat-bar-fill" :style="{ width: s.percentage + '%', background: colors[i % colors.length] }" />
              </div>
              <span class="stat-pct font-display">{{ s.percentage }}%</span>
              <span class="stat-count">{{ s.count }}x</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from '@/Composables/useI18n'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

const props = defineProps({ group: Object, items: Array, session: Object, duel: Object, stats: Array })
const { add: toast } = useToast()
const { t, tp } = useI18n()

const starting     = ref(false)
const choosing     = ref(false)
const duelKey      = ref(0)
const localSession = ref(props.session ?? null)
const currentDuel  = ref(props.duel ?? null)
const localStats   = ref(props.stats ?? null)
const colors       = ['#f97316','#6366f1','#ec4899','#22c55e','#3b82f6','#a855f7']

const totalSessions  = computed(() => localStats.value?.reduce((s, i) => s + i.count, 0) ?? 0)
const eliminationPct = computed(() => {
  if (!localSession.value) return 0
  return Math.round((1 - localSession.value.remaining_count / props.items.length) * 100)
})

const startTournament = async () => {
  starting.value = true
  try {
    const { data } = await axios.post(route('groups.elimination.start', props.group.id))
    localSession.value = { remaining_count: data.remaining_count, completed: false }
    currentDuel.value  = data.duel
    duelKey.value++
  } catch (e) {
    toast(t('common.error'), 'error')
  } finally {
    starting.value = false
  }
}

const choose = async (winnerId) => {
  if (choosing.value) return
  choosing.value = true
  try {
    const { data } = await axios.post(route('groups.elimination.choose', props.group.id), { winner_id: winnerId })
    localSession.value = { remaining_count: data.remaining_count, completed: data.completed, winner: data.winner ?? null }
    if (data.completed) {
      localStats.value = data.stats
      toast(t('elimination.champion'), 'success')
    } else {
      currentDuel.value = data.duel
      duelKey.value++
    }
  } catch (e) {
    toast('Erreur.', 'error')
  } finally {
    choosing.value = false
  }
}

const restartTournament = async () => {
  localSession.value = null
  currentDuel.value  = null
  await startTournament()
}
</script>

<style scoped>
.elim-layout { max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.elim-hero { padding: 1.75rem; }
.elim-meta { display: flex; align-items: center; gap: .75rem; margin-bottom: .75rem; font-size: .8rem; color: var(--color-text-muted); }
.elim-badge { background: rgba(245,158,11,.15); color: #f59e0b; padding: .2rem .7rem; border-radius: 999px; font-weight: 600; }
.elim-title { font-size: 1.5rem; font-weight: 800; margin: 0 0 .5rem; }
.elim-desc { color: var(--color-text-muted); font-size: .9rem; margin: 0; }
.auth-prompt { padding: 2.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 1rem; }
.auth-icon { font-size: 2.5rem; }
.auth-prompt h2 { font-size: 1.3rem; margin: 0; }
.auth-prompt p { color: var(--color-text-muted); margin: 0; }
.auth-btns { display: flex; gap: .75rem; flex-wrap: wrap; justify-content: center; }
.start-screen { padding: 2.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 1.25rem; }
.start-icon { font-size: 3rem; }
.start-screen h2 { font-size: 1.4rem; margin: 0; }
.start-screen p { color: var(--color-text-muted); margin: 0; max-width: 400px; }
.items-preview { display: flex; flex-wrap: wrap; gap: .4rem; justify-content: center; max-width: 480px; }
.item-chip { padding: .3rem .7rem; background: var(--color-surface-2); border: 1px solid var(--color-border); border-radius: 999px; font-size: .8rem; color: var(--color-text-muted); display: flex; align-items: center; gap: .3rem; }
.item-chip-img { width: 18px; height: 18px; border-radius: 50%; object-fit: cover; }
.item-chip-more { color: var(--color-accent); border-color: var(--color-accent-soft); }
.start-btn { padding: .8rem 2rem; font-size: 1rem; }
.duel-progress { padding: 1rem 1.5rem; display: flex; align-items: center; gap: 1rem; }
.duel-progress-info { display: flex; flex-direction: column; gap: .1rem; flex-shrink: 0; }
.duel-remaining { font-size: 1.1rem; font-weight: 800; color: var(--color-accent); }
.duel-hint { font-size: .75rem; color: var(--color-text-muted); }
.duel-progress-bar { flex: 1; height: 8px; background: var(--color-border); border-radius: 4px; overflow: hidden; }
.duel-progress-fill { height: 100%; background: linear-gradient(to right, var(--color-accent), #f59e0b); border-radius: 4px; transition: width .5s ease; }
.duel-cards { display: grid; grid-template-columns: 1fr auto 1fr; gap: 1rem; align-items: center; }
.duel-card { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 2rem 1.25rem; background: var(--color-surface); border: 2px solid var(--color-border); border-radius: var(--radius-lg); cursor: pointer; transition: all .2s; min-height: 160px; justify-content: center; }
.duel-a:not(:disabled):hover { border-color: #6366f1; background: rgba(99,102,241,.08); transform: translateY(-4px); box-shadow: 0 8px 30px rgba(99,102,241,.2); }
.duel-b:not(:disabled):hover { border-color: #ec4899; background: rgba(236,72,153,.08); transform: translateY(-4px); box-shadow: 0 8px 30px rgba(236,72,153,.2); }
.duel-card:disabled { opacity: .6; cursor: default; }
.duel-img { width: 100%; max-height: 120px; object-fit: cover; border-radius: var(--radius-sm); }
.duel-label { font-size: 1.1rem; font-weight: 700; text-align: center; color: var(--color-text); line-height: 1.3; }
.duel-vs { display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: var(--color-accent); color: white; font-size: .75rem; font-weight: 900; box-shadow: var(--shadow-accent); flex-shrink: 0; }
.duel-enter-active, .duel-leave-active { transition: all .25s ease; }
.duel-enter-from { opacity: 0; transform: scale(.95); }
.duel-leave-to { opacity: 0; transform: scale(1.02); }
.result-screen { display: flex; flex-direction: column; gap: 1.25rem; }
.winner-card { padding: 2.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 1rem; }
.winner-crown { font-size: 3rem; }
.winner-title { font-size: 1.5rem; font-weight: 800; margin: 0; }
.winner-item { display: flex; flex-direction: column; align-items: center; gap: .75rem; }
.winner-img { width: 120px; height: 120px; object-fit: cover; border-radius: var(--radius-md); }
.winner-label { font-size: 1.75rem; font-weight: 800; color: var(--color-accent); }
.global-stats { padding: 1.75rem; }
.stats-title { font-size: 1rem; font-weight: 800; margin: 0 0 .3rem; }
.stats-subtitle { font-size: .8rem; color: var(--color-text-muted); margin: 0 0 1.25rem; }
.stats-list { display: flex; flex-direction: column; gap: .6rem; }
.stat-row { display: flex; align-items: center; gap: .6rem; }
.stat-rank { width: 24px; text-align: center; font-size: .85rem; font-weight: 800; color: var(--color-text-muted); flex-shrink: 0; }
.stat-img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.stat-label { font-size: .875rem; font-weight: 600; width: 100px; flex-shrink: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.stat-bar-wrap { flex: 1; height: 8px; background: var(--color-border); border-radius: 4px; overflow: hidden; }
.stat-bar-fill { height: 100%; border-radius: 4px; transition: width .8s cubic-bezier(.4,0,.2,1); }
.stat-pct { font-size: .875rem; font-weight: 800; color: var(--color-text); width: 38px; text-align: right; flex-shrink: 0; }
.stat-count { font-size: .72rem; color: var(--color-text-muted); width: 28px; flex-shrink: 0; }
@media (max-width: 540px) { .duel-cards { grid-template-columns: 1fr; } .duel-vs { margin: -.5rem auto; z-index: 1; } }
</style>
