<template>
  <article class="question-card animate-fade-up" :style="{ animationDelay: delay + 'ms' }">
    <div class="card-header">
      <div class="card-meta">
        <span class="category-badge">{{ categoryEmoji }} {{ question.category }}</span>
        <span class="meta-dot">·</span>
        <span class="meta-time">{{ question.created_at }}</span>
      </div>
      <div class="card-author" v-if="question.author" @click="goToProfile(question.author.id)" style="cursor:pointer">
        <img :src="question.author.avatar_url" :alt="question.author.name" class="author-avatar" />
        <span class="author-name">{{ question.author.name }}</span>
      </div>
    </div>

    <h2 v-if="question.title" class="card-title font-display">{{ question.title }}</h2>

    <div class="vs-label">
      <div class="vs-line" /><span class="vs-text font-display">{{ t('question.tu_preferes') }}</span><div class="vs-line" />
    </div>

    <div class="options-wrap">
      <button
        v-for="(option, idx) in localOptions" :key="option.id"
        class="option-btn"
        :class="[
          idx === 0 ? 'option-a' : 'option-b',
          { 'option-chosen': localVote === option.id },
          { 'option-other':  localVote !== null && localVote !== option.id },
        ]"
        @click="handleVote(option.id)"
        :disabled="localVote !== null || voteLoading"
      >
        <div v-if="option.image" class="option-image-wrap">
          <img :src="option.image" :alt="option.label" class="option-image" />
        </div>
        <div v-if="option.audio" class="option-audio">
          <button class="audio-btn" @click.stop="toggleAudio(option.id, option.audio)">
            {{ playingAudio === option.id ? '⏸' : '▶' }}
          </button>
        </div>
        <span class="option-label">{{ option.label }}</span>
        <Transition name="stats">
          <div v-if="localVote !== null" class="option-stats">
            <div class="progress-bar">
              <div class="progress-fill" :class="idx === 0 ? 'progress-a' : 'progress-b'" :style="{ width: option.percentage + '%' }" />
            </div>
            <div class="stats-numbers">
              <span class="stat-pct font-display">{{ option.percentage }}%</span>
              <span class="stat-count">{{ option.vote_count }} vote{{ option.vote_count !== 1 ? 's' : '' }}</span>
            </div>
          </div>
        </Transition>
        <div v-if="localVote === option.id" class="chosen-mark">✓</div>
      </button>
    </div>

    <Transition name="fade">
      <div v-if="localVote !== null" class="total-votes-row">
        {{ localTotalVotes }} {{ tp('question.votes_total', localTotalVotes) }} au total
      </div>
    </Transition>

    <div class="card-footer">
      <template v-if="$page.props.auth.user">
        <button class="action-btn" :class="{ 'action-liked': localLiked }" @click="toggleLike">
          <HeartIcon :filled="localLiked" /><span>{{ localLikes }}</span>
        </button>
        <button class="action-btn" @click="shareQuestion">
          <ShareIcon /><span>{{ localShares }}</span>
        </button>
      </template>
      <Link :href="route('questions.show', question.id)" class="action-btn action-link">{{ t('question.see') }}</div>
      <button
        v-if="$page.props.auth.user && question.author && $page.props.auth.user.id === question.author.id"
        class="action-btn action-danger" @click="deleteQuestion"
      ><TrashIcon /></button>
    </div>
  </article>

  <!-- Modal signalement -->
  <Teleport to="body">
    <div v-if="reportModal" class="modal-overlay" @click.self="reportModal = false">
      <div class="modal-box card">
        <h3 class="font-display modal-title">🚩 Signaler ce post</h3>
        <div class="field">
          <label class="field-label">Raison</label>
          <select v-model="reportReason" class="field-input">
            <option value="inapproprie">Inapproprié</option>
            <option value="spam">Spam</option>
            <option value="harcelement">Harcèlement</option>
            <option value="fausse_information">Fausse information</option>
            <option value="autre">Autre</option>
          </select>
        </div>
        <div class="field">
          <label class="field-label">Commentaire (optionnel)</label>
          <textarea v-model="reportComment" class="field-input" rows="3" placeholder="Décris le problème..." />
        </div>
        <div class="modal-actions">
          <button class="btn-ghost" @click="reportModal = false">Annuler</button>
          <button class="btn-primary" @click="submitReport">Envoyer le signalement</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from '@/Composables/useI18n'
import { Link, router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'
import HeartIcon from '@/Components/Icons/HeartIcon.vue'
import ShareIcon from '@/Components/Icons/ShareIcon.vue'
import TrashIcon from '@/Components/Icons/TrashIcon.vue'

const props = defineProps({
  question: { type: Object, required: true },
  delay:    { type: Number, default: 0 },
})
const emit = defineEmits(['updated', 'vote'])
const { add: toast } = useToast()
const { t, tp } = useI18n()

const localVote       = ref(props.question.user_vote)
const localOptions    = ref([...props.question.options])
const localTotalVotes = ref(props.question.total_votes)
const localLiked      = ref(props.question.has_liked)
const localLikes      = ref(props.question.total_likes)
const localShares     = ref(props.question.total_shares)
const voteLoading     = ref(false)
const playingAudio    = ref(null)
let audioEl = null
const reportModal   = ref(false)
const reportReason  = ref('inapproprie')
const reportComment = ref('')

const submitReport = async () => {
  try {
    await axios.post(route('questions.report', props.question.id), {
      reason:  reportReason.value,
      comment: reportComment.value,
    })
    reportModal.value   = false
    reportComment.value = ''
    toast('Signalement envoyé. Merci')
  } catch { toast('Erreur.', 'error') }
}

const categoryEmojis = { amour:'❤️', aventure:'🗺️', nourriture:'🍕', technologie:'💻', voyage:'✈️', sport:'⚽', musique:'🎵', 'cinéma':'🎬', divers:'🎲' }
const categoryEmoji = computed(() => categoryEmojis[props.question.category] || '🎲')

const handleVote = async (optionId) => {
  if (localVote.value !== null || voteLoading.value) return

  // Mode groupe : déléguer au parent
  if (props.question.group_mode) {
    emit('vote', optionId)
    return
  }

  if (!usePage().props.auth.user) { router.visit(route('login')); return }

  voteLoading.value = true
  try {
    const { data } = await axios.post(route('questions.vote', props.question.id), { option_id: optionId })
    localVote.value       = optionId
    localOptions.value    = data.question.options
    localTotalVotes.value = data.question.total_votes
    emit('updated', data.question)
    toast(t('question.vote_success'))
  } catch (e) {
    toast(e.response?.data?.message || t('common.error'), 'error')
  } finally {
    voteLoading.value = false
  }
}

const toggleLike = async () => {
  try {
    const { data } = await axios.post(route('questions.like', props.question.id))
    localLiked.value = data.liked
    localLikes.value = data.total_likes
  } catch { toast('Erreur.', 'error') }
}

const shareQuestion = async () => {
  const url = route('questions.show', props.question.id)
  try {
    if (navigator.share) await navigator.share({ title: 'Tu préfères ?', url })
    else { await navigator.clipboard.writeText(url); toast(t('question.link_copied')) }
    const { data } = await axios.post(route('questions.share', props.question.id), { platform: 'link' })
    localShares.value = data.total_shares
  } catch {}
}

const deleteQuestion = () => {
  if (!confirm(t('question.delete_confirm'))) return
  router.delete(route('questions.destroy', props.question.id))
}

const toggleAudio = (optionId, url) => {
  if (playingAudio.value === optionId) { audioEl?.pause(); playingAudio.value = null; return }
  audioEl?.pause()
  audioEl = new Audio(url)
  audioEl.play()
  playingAudio.value = optionId
  audioEl.onended = () => { playingAudio.value = null }
}
</script>

<style scoped>
.question-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 1.5rem; transition: box-shadow .25s, transform .25s; }
.question-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: .5rem; }
.card-meta { display: flex; align-items: center; gap: .4rem; font-size: .8rem; color: var(--color-text-muted); }
.category-badge { background: var(--color-accent-soft); color: var(--color-accent); padding: .2rem .6rem; border-radius: 999px; font-size: .75rem; font-weight: 600; text-transform: capitalize; }
.meta-dot { opacity: .4; }
.card-author { display: flex; align-items: center; gap: .4rem; }
.author-avatar { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; }
.author-name { font-size: .8rem; color: var(--color-text-muted); }
.card-title { font-size: 1.1rem; font-weight: 700; margin: 0 0 1rem; line-height: 1.4; }
.vs-label { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.vs-line { flex: 1; height: 1px; background: var(--color-border); }
.vs-text { font-size: .65rem; font-weight: 800; letter-spacing: .1em; color: var(--color-text-faint); white-space: nowrap; }
.options-wrap { display: flex; gap: .75rem; margin-bottom: 1rem; }
.option-btn { flex: 1; display: flex; flex-direction: column; align-items: center; gap: .6rem; padding: 1.25rem 1rem; border: 2px solid var(--color-border); border-radius: var(--radius-md); background: var(--color-surface-2); cursor: pointer; transition: all .2s; position: relative; text-align: center; min-height: 100px; }
.option-btn:not(:disabled):hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
.option-a { border-color: rgba(99,102,241,.3); }
.option-a:not(:disabled):hover, .option-a.option-chosen { border-color: #6366f1; background: rgba(99,102,241,.1); }
.option-b { border-color: rgba(236,72,153,.3); }
.option-b:not(:disabled):hover, .option-b.option-chosen { border-color: #ec4899; background: rgba(236,72,153,.1); }
.option-other { opacity: .55; }
.option-btn:disabled { cursor: default; }
.option-image-wrap { width: 100%; max-height: 120px; overflow: hidden; border-radius: var(--radius-sm); }
.option-image { width: 100%; height: 120px; object-fit: cover; }
.audio-btn { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; }
.option-label { font-weight: 600; font-size: .9rem; color: var(--color-text); line-height: 1.4; }
.option-stats { width: 100%; }
.progress-bar { height: 6px; background: var(--color-border); border-radius: 3px; overflow: hidden; margin-bottom: .4rem; }
.progress-fill { height: 100%; border-radius: 3px; transition: width .8s cubic-bezier(.4,0,.2,1); }
.stats-numbers { display: flex; justify-content: space-between; align-items: baseline; }
.stat-pct { font-weight: 800; font-size: 1rem; color: var(--color-text); }
.stat-count { font-size: .75rem; color: var(--color-text-muted); }
.chosen-mark { position: absolute; top: .5rem; right: .5rem; width: 20px; height: 20px; border-radius: 50%; background: var(--color-accent); color: white; font-size: .65rem; display: flex; align-items: center; justify-content: center; font-weight: 800; }
.total-votes-row { text-align: center; font-size: .78rem; color: var(--color-text-muted); margin-bottom: .75rem; }
.card-footer { display: flex; align-items: center; gap: .5rem; padding-top: .75rem; border-top: 1px solid var(--color-border); }
.action-btn { display: flex; align-items: center; gap: .3rem; padding: .35rem .75rem; background: none; border: 1px solid var(--color-border); border-radius: var(--radius-sm); color: var(--color-text-muted); font-size: .8rem; cursor: pointer; text-decoration: none; transition: all .2s; font-family: var(--font-body); }
.action-btn:hover { background: var(--color-surface-2); color: var(--color-text); }
.action-btn svg { width: 16px; height: 16px; }
.action-liked { color: #ec4899 !important; border-color: rgba(236,72,153,.4) !important; }
.action-link { margin-left: auto; color: var(--color-accent); border-color: var(--color-accent-soft); }
.action-link:hover { background: var(--color-accent-soft); }
.action-danger:hover { color: #ef4444; border-color: rgba(239,68,68,.3); }
.stats-enter-active { transition: all .4s ease; }
.stats-enter-from { opacity: 0; transform: translateY(6px); }
.fade-enter-active { transition: opacity .3s ease; }
.fade-enter-from { opacity: 0; }
@media (max-width: 480px) { .options-wrap { flex-direction: column; } .question-card { padding: 1rem; } }
</style>

<style>
.action-report:hover { color: #ef4444; border-color: rgba(239,68,68,.3); }
.modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;display:flex;align-items:center;justify-content:center;padding:1rem; }
.modal-box { padding:2rem;max-width:440px;width:100%;display:flex;flex-direction:column;gap:1.25rem; }
.modal-title { font-size:1.25rem;font-weight:800;margin:0; }
.modal-actions { display:flex;justify-content:flex-end;gap:.75rem; }
.field { display:flex;flex-direction:column;gap:.35rem; }
.field-label { font-size:.8rem;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.04em; }
.field-input { width:100%;box-sizing:border-box;background:var(--color-surface-2);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:.7rem .9rem;color:var(--color-text);font-family:var(--font-body);font-size:.9rem;outline:none;resize:vertical; }
.field-input:focus { border-color:var(--color-accent); }
</style>