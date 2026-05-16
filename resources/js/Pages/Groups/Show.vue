<template>
  <AppLayout>
    <div class="group-layout">
      <div class="group-hero card">
        <div class="group-meta">
          <span class="group-badge">📦 Groupe · {{ group.total_questions }} questions</span>
          <span class="group-time">{{ group.created_at }}</span>
        </div>
        <h1 class="font-display group-title">{{ group.title }}</h1>
        <p v-if="group.description" class="group-desc">{{ group.description }}</p>
        <div class="progress-section">
          <div class="progress-bar-wrap">
            <div class="progress-bar-fill" :style="{ width: progressPct + '%' }" />
          </div>
          <span class="progress-label">{{ answeredCount }} / {{ group.total_questions }}</span>
        </div>
      </div>
      <div class="questions-chain">
        <div
          v-for="(q, idx) in questions"
          :key="q.id"
          class="chain-item"
          :class="{ 'chain-locked': idx > currentIndex }"
        >
          <div class="chain-connector">
            <div class="chain-num font-display" :class="{ done: q.user_vote !== null }">
              {{ q.user_vote !== null ? '✓' : idx + 1 }}
            </div>
            <div v-if="idx < group.questions.length - 1" class="chain-line" :class="{ done: q.user_vote !== null }" />
          </div>
          <div class="chain-content">
            <QuestionCard :question="q" :delay="0" @vote="onVote(idx, q.id, $event)" @updated="questions[idx] = $event" />
          </div>
        </div>
      </div>
      <div v-if="allDone" class="group-completed card">
        <div class="completed-icon">🎉</div>
        <h2 class="font-display">Groupe terminé !</h2>
        <p>Tu as répondu à toutes les questions.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import QuestionCard from '@/Components/QuestionCard.vue'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

const props = defineProps({ group: Object })
const { add: toast } = useToast()

const questions    = ref(props.group.questions)
const currentIndex = ref(props.group.current_position ?? 0)

const answeredCount = computed(() => questions.value.filter(q => q.user_vote !== null).length)
const progressPct   = computed(() => Math.round(answeredCount.value / questions.value.length * 100))
const allDone       = computed(() => answeredCount.value === questions.value.length)

const onVote = async (idx, questionId, optionId) => {
  if (!usePage().props.auth.user) return
  try {
    const { data } = await axios.post(
      route('groups.vote', { group: props.group.id, question: questionId }),
      { option_id: optionId }
    )
    questions.value[idx] = data.question
    if (data.next_question_id) {
      currentIndex.value = idx + 1
      setTimeout(() => {
        const el = document.querySelectorAll('.chain-item')[idx + 1]
        el?.scrollIntoView({ behavior: 'smooth', block: 'center' })
      }, 500)
    }
    if (data.completed) toast('Groupe terminé ! 🎉', 'success')
  } catch (e) {
    toast(e.response?.data?.message || 'Erreur.', 'error')
  }
}
</script>

<style scoped>
.group-layout { max-width: 680px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.group-hero { padding: 1.75rem; }
.group-meta { display: flex; align-items: center; gap: .75rem; margin-bottom: .75rem; font-size: .8rem; color: var(--color-text-muted); }
.group-badge { background: var(--color-accent-soft); color: var(--color-accent); padding: .2rem .7rem; border-radius: 999px; font-weight: 600; }
.group-title { font-size: 1.5rem; font-weight: 800; margin: 0 0 .5rem; }
.group-desc { color: var(--color-text-muted); font-size: .9rem; margin: 0 0 1rem; }
.progress-section { display: flex; align-items: center; gap: .75rem; margin-top: 1rem; }
.progress-bar-wrap { flex: 1; height: 8px; background: var(--color-border); border-radius: 4px; overflow: hidden; }
.progress-bar-fill { height: 100%; background: var(--color-accent); border-radius: 4px; transition: width .5s ease; }
.progress-label { font-size: .8rem; color: var(--color-text-muted); font-weight: 600; white-space: nowrap; }
.questions-chain { display: flex; flex-direction: column; }
.chain-item { display: flex; gap: 1rem; transition: opacity .3s; }
.chain-locked { opacity: .4; pointer-events: none; }
.chain-connector { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; }
.chain-num { width: 32px; height: 32px; border-radius: 50%; background: var(--color-surface-2); border: 2px solid var(--color-border); color: var(--color-text-muted); font-size: .8rem; font-weight: 800; display: flex; align-items: center; justify-content: center; transition: all .3s; }
.chain-num.done { background: var(--color-accent); border-color: var(--color-accent); color: white; }
.chain-line { width: 2px; flex: 1; min-height: 1rem; background: var(--color-border); margin: .25rem 0; transition: background .3s; }
.chain-line.done { background: var(--color-accent); }
.chain-content { flex: 1; padding-bottom: 1.25rem; }
.group-completed { padding: 2.5rem; text-align: center; }
.completed-icon { font-size: 2.5rem; margin-bottom: .75rem; }
.group-completed h2 { font-size: 1.4rem; margin: 0 0 .5rem; }
.group-completed p { color: var(--color-text-muted); margin: 0; }
</style>
