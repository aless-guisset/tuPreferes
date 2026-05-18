<template>
  <AppLayout>
    <div class="create-layout">
      <div class="create-header">
        <Link :href="route('groups.index')" class="back-link">{{ t("common.back") }}</Link>
        <h1 class="font-display create-title">{{ t("create.title") }}</h1>
      </div>

      <!-- Sélecteur de type -->
      <div class="type-selector">
        <button v-for="t in types" :key="t.id" class="type-card" :class="{ active: form.type === t.id }" @click="form.type = t.id">
          <span class="type-icon">{{ t.icon }}</span>
          <span class="type-label font-display">{{ t.label }}</span>
          <span class="type-desc">{{ t.desc }}</span>
        </button>
      </div>

      <!-- SIMPLE -->
      <form v-if="form.type === 'simple'" class="form card" @submit.prevent="submitSimple">
        <div class="field">
          <label class="field-label">Titre <span class="optional">(optionnel)</span></label>
          <input v-model="form.title" type="text" class="field-input" placeholder="Ex: Le plus grand dilemme..." maxlength="255" />
        </div>
        <div class="field">
          <label class="field-label">Catégorie <span class="req">*</span></label>
          <div class="cat-grid">
            <button v-for="cat in categories" :key="cat.value" type="button" class="cat-btn" :class="{ active: form.category === cat.value }" @click="form.category = cat.value">{{ cat.emoji }} {{ cat.label }}</button>
          </div>
        </div>
        <div class="vs-row"><div class="vs-line"/><span class="vs-text font-display">TU PRÉFÈRES</span><div class="vs-line"/></div>
        <div class="options-grid">
          <div v-for="(opt, i) in form.options" :key="i" class="opt-card" :class="i === 0 ? 'opt-a' : 'opt-b'">
            <span class="opt-letter font-display">{{ i === 0 ? 'A' : 'B' }}</span>
            <textarea v-model="opt.label" class="field-input field-ta" :placeholder="i === 0 ? 'Première option...' : 'Deuxième option...'" rows="2" maxlength="255" required />
            <label class="file-drop" @dragover.prevent @drop.prevent="onDropSimple($event, i)">
              <input type="file" accept="image/*" class="hidden" @change="onImgSimple($event, i)" />
              <img v-if="opt.imagePreview" :src="opt.imagePreview" class="preview-img" />
              <span v-else class="file-ph">🖼️ Image (optionnel)</span>
            </label>
            <label class="file-drop file-drop-sm">
              <input type="file" accept=".mp3,.wav,.ogg,.m4a" class="hidden" @change="onAudioSimple($event, i)" />
              <span v-if="opt.audioName" class="audio-name">🎵 {{ opt.audioName }}</span>
              <span v-else class="file-ph">🎵 Audio (optionnel)</span>
            </label>
          </div>
        </div>
        <label class="toggle-row">
          <div class="toggle-track" :class="{ active: form.is_anonymous }" @click="form.is_anonymous = !form.is_anonymous"><div class="toggle-thumb"/></div>
          <span>Publier anonymement</span>
        </label>
        <div class="form-actions">
          <Link :href="route('questions.index')" class="btn-ghost">{{ t("create.cancel") }}</Link>
          <button type="submit" class="btn-primary" :disabled="loading">{{ loading ? '⏳...' : '🚀 Publier' }}</button>
        </div>
      </form>

      <!-- GROUPE -->
      <form v-if="form.type === 'group'" class="form card" @submit.prevent="submitGroup">
        <div class="field">
          <label class="field-label">Titre du groupe <span class="req">*</span></label>
          <input v-model="form.groupTitle" type="text" class="field-input" placeholder="Ex: Quel fruit préfères-tu ?" required maxlength="255" />
        </div>
        <div class="field">
          <label class="field-label">Description <span class="optional">(optionnel)</span></label>
          <textarea v-model="form.groupDescription" class="field-input field-ta" rows="2" maxlength="500" />
        </div>
        <div class="field">
          <label class="field-label">Catégorie <span class="req">*</span></label>
          <div class="cat-grid">
            <button v-for="cat in categories" :key="cat.value" type="button" class="cat-btn" :class="{ active: form.category === cat.value }" @click="form.category = cat.value">{{ cat.emoji }} {{ cat.label }}</button>
          </div>
        </div>
        <div class="group-questions">
          <div class="gq-header">
            <span class="font-display gq-title">Questions ({{ form.groupQuestions.length }})</span>
            <button type="button" class="btn-ghost btn-sm" @click="addQ" :disabled="form.groupQuestions.length >= 20">+ Ajouter</button>
          </div>
          <div v-for="(q, qi) in form.groupQuestions" :key="qi" class="gq-item">
            <div class="gq-num font-display">{{ qi + 1 }}</div>
            <div class="gq-content">
              <input v-model="q.title" type="text" class="field-input" :placeholder="`Question ${qi+1} (optionnel)`" maxlength="255" />
              <div class="options-grid-sm">
                <div v-for="(opt, oi) in q.options" :key="oi" class="opt-sm" :class="oi === 0 ? 'opt-a' : 'opt-b'">
                  <span class="opt-letter-sm font-display">{{ oi === 0 ? 'A' : 'B' }}</span>
                  <input v-model="opt.label" type="text" class="field-input" :placeholder="`Option ${oi === 0 ? 'A' : 'B'}`" required maxlength="255" />
                  <label class="img-upload"><input type="file" accept="image/*" class="hidden" @change="onGImg($event, qi, oi)" /><img v-if="opt.imagePreview" :src="opt.imagePreview" class="mini-img" /><span v-else class="mini-ph">🖼</span></label>
                </div>
              </div>
            </div>
            <button type="button" class="rm-btn" @click="rmQ(qi)" v-if="form.groupQuestions.length > 2">✕</button>
          </div>
        </div>
        <label class="toggle-row">
          <div class="toggle-track" :class="{ active: form.is_anonymous }" @click="form.is_anonymous = !form.is_anonymous"><div class="toggle-thumb"/></div>
          <span>Publier anonymement</span>
        </label>
        <div class="form-actions">
          <Link :href="route('groups.index')" class="btn-ghost">{{ t("create.cancel") }}</Link>
          <button type="submit" class="btn-primary" :disabled="loading || form.groupQuestions.length < 2">{{ loading ? '⏳...' : '🚀 Créer le groupe' }}</button>
        </div>
      </form>

      <!-- ÉLIMINATOIRE -->
      <form v-if="form.type === 'elimination'" class="form card" @submit.prevent="submitElim">
        <div class="field">
          <label class="field-label">Titre du tournoi <span class="req">*</span></label>
          <input v-model="form.elimTitle" type="text" class="field-input" placeholder="Ex: Le fruit que tu préfères manger en premier" required maxlength="255" />
        </div>
        <div class="field">
          <label class="field-label">Description <span class="optional">(optionnel)</span></label>
          <textarea v-model="form.elimDescription" class="field-input field-ta" rows="2" maxlength="500" />
        </div>
        <div class="field">
          <label class="field-label">Ordre des duels</label>
          <div class="order-btns">
            <button type="button" class="order-btn" :class="{ active: form.elimOrder === 'sequential' }" @click="form.elimOrder = 'sequential'">📋 {{ t("elimination.order_sequential") }}</button>
            <button type="button" class="order-btn" :class="{ active: form.elimOrder === 'random' }" @click="form.elimOrder = 'random'">🎲 {{ t("elimination.order_random") }}</button>
          </div>
        </div>
        <div class="elim-section">
          <div class="elim-header">
            <span class="font-display">Items ({{ form.elimItems.length }})</span>
            <span class="elim-hint">{{ t("create.items_hint") }}</span>
            <button type="button" class="btn-ghost btn-sm" @click="addItem">+ Ajouter</button>
          </div>
          <div class="elim-list">
            <div v-for="(item, i) in form.elimItems" :key="i" class="elim-row">
              <span class="elim-num">{{ i + 1 }}</span>
              <input v-model="item.label" type="text" class="field-input" :placeholder="`Item ${i+1} (ex: Pomme)`" required maxlength="100" />
              <label class="img-upload"><input type="file" accept="image/*" class="hidden" @change="onEImg($event, i)" /><img v-if="item.imagePreview" :src="item.imagePreview" class="mini-img" /><span v-else class="mini-ph">🖼</span></label>
              <button type="button" class="rm-btn" @click="rmItem(i)" v-if="form.elimItems.length > 3">✕</button>
            </div>
          </div>
        </div>
        <label class="toggle-row">
          <div class="toggle-track" :class="{ active: form.is_anonymous }" @click="form.is_anonymous = !form.is_anonymous"><div class="toggle-thumb"/></div>
          <span>Publier anonymement</span>
        </label>
        <div class="form-actions">
          <Link :href="route('groups.index')" class="btn-ghost">{{ t("create.cancel") }}</Link>
          <button type="submit" class="btn-primary" :disabled="loading || form.elimItems.length < 3">{{ loading ? '⏳...' : '🏆 Créer le tournoi' }}</button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useI18n } from '@/Composables/useI18n'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useToast } from '@/Composables/useToast'

const { add: toast } = useToast()
const { t } = useI18n()
const loading = ref(false)

const types = [
  { id: 'simple',      icon: '⚡', label: 'Simple',      desc: '{{ t("create.type_simple_desc") }}' },
  { id: 'group',       icon: '📦', label: 'Groupe',       desc: '{{ t("create.type_group_desc") }}' },
  { id: 'elimination', icon: '🏆', label: 'Éliminatoire', desc: 'Tournoi jusqu\'au dernier' },
]

const categories = [
  { value: 'amour', label: 'Amour', emoji: '❤️' },
  { value: 'aventure', label: 'Aventure', emoji: '🗺️' },
  { value: 'nourriture', label: 'Nourriture', emoji: '🍕' },
  { value: 'technologie', label: 'Technologie', emoji: '💻' },
  { value: 'voyage', label: 'Voyage', emoji: '✈️' },
  { value: 'sport', label: 'Sport', emoji: '⚽' },
  { value: 'musique', label: 'Musique', emoji: '🎵' },
  { value: 'cinéma', label: 'Cinéma', emoji: '🎬' },
  { value: 'divers', label: 'Divers', emoji: '🎲' },
]

const makeOpt = () => ({ label: '', image: null, imagePreview: null, audio: null, audioName: null })
const makeGQ  = () => ({ title: '', options: [makeOpt(), makeOpt()] })

const form = reactive({
  type: 'simple',
  title: '', category: 'divers', is_anonymous: false,
  options: [makeOpt(), makeOpt()],
  groupTitle: '', groupDescription: '',
  groupQuestions: [makeGQ(), makeGQ()],
  elimTitle: '', elimDescription: '', elimOrder: 'sequential',
  elimItems: Array.from({ length: 4 }, () => ({ label: '', image: null, imagePreview: null })),
})

const onImgSimple = (e, i) => { const f = e.target.files[0]; if (!f) return; form.options[i].image = f; form.options[i].imagePreview = URL.createObjectURL(f) }
const onAudioSimple = (e, i) => { const f = e.target.files[0]; if (!f) return; form.options[i].audio = f; form.options[i].audioName = f.name }
const onDropSimple = (e, i) => { const f = e.dataTransfer.files[0]; if (f) onImgSimple({ target: { files: [f] } }, i) }
const onGImg = (e, qi, oi) => { const f = e.target.files[0]; if (!f) return; form.groupQuestions[qi].options[oi].image = f; form.groupQuestions[qi].options[oi].imagePreview = URL.createObjectURL(f) }
const onEImg = (e, i) => { const f = e.target.files[0]; if (!f) return; form.elimItems[i].image = f; form.elimItems[i].imagePreview = URL.createObjectURL(f) }
const addQ    = () => form.groupQuestions.push(makeGQ())
const rmQ     = (i) => form.groupQuestions.splice(i, 1)
const addItem = () => form.elimItems.push({ label: '', image: null, imagePreview: null })
const rmItem  = (i) => form.elimItems.splice(i, 1)

const submitSimple = () => {
  loading.value = true
  const d = new FormData()
  if (form.title) d.append('title', form.title)
  d.append('category', form.category)
  d.append('is_anonymous', form.is_anonymous ? '1' : '0')
  d.append('type', 'simple')
  form.options.forEach((o, i) => { d.append(`options[${i}][label]`, o.label); if (o.image) d.append(`options[${i}][image]`, o.image); if (o.audio) d.append(`options[${i}][audio]`, o.audio) })
  router.post(route('groups.store'), d, { onError: (e) => toast('t("create.fix_errors")', 'error'), onFinish: () => { loading.value = false } })
}

const submitGroup = () => {
  loading.value = true
  const d = new FormData()
  d.append('type', 'group')
  d.append('title', form.groupTitle)
  if (form.groupDescription) d.append('description', form.groupDescription)
  d.append('category', form.category)
  d.append('is_anonymous', form.is_anonymous ? '1' : '0')
  form.groupQuestions.forEach((q, qi) => { if (q.title) d.append(`questions[${qi}][title]`, q.title); q.options.forEach((o, oi) => { d.append(`questions[${qi}][options][${oi}][label]`, o.label); if (o.image) d.append(`questions[${qi}][options][${oi}][image]`, o.image) }) })
  router.post(route('groups.store'), d, { onError: (e) => toast('t("create.fix_errors")', 'error'), onFinish: () => { loading.value = false } })
}

const submitElim = () => {
  loading.value = true
  const d = new FormData()
  d.append('type', 'elimination')
  d.append('title', form.elimTitle)
  if (form.elimDescription) d.append('description', form.elimDescription)
  d.append('order', form.elimOrder)
  d.append('is_anonymous', form.is_anonymous ? '1' : '0')
  form.elimItems.forEach((item, i) => { d.append(`items[${i}][label]`, item.label); if (item.image) d.append(`items[${i}][image]`, item.image) })
  router.post(route('groups.store'), d, { onError: (e) => toast('t("create.fix_errors")', 'error'), onFinish: () => { loading.value = false } })
}
</script>

<style scoped>
.create-layout { max-width: 760px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.back-link { color: var(--color-text-muted); text-decoration: none; font-size: .875rem; }
.back-link:hover { color: var(--color-accent); }
.create-title { font-size: 1.75rem; font-weight: 800; margin: .25rem 0 0; }
.type-selector { display: grid; grid-template-columns: repeat(3,1fr); gap: 1rem; }
.type-card { display: flex; flex-direction: column; align-items: center; gap: .4rem; padding: 1.25rem 1rem; background: var(--color-surface); border: 2px solid var(--color-border); border-radius: var(--radius-lg); cursor: pointer; transition: all .2s; text-align: center; }
.type-card:hover { border-color: var(--color-accent); }
.type-card.active { border-color: var(--color-accent); background: var(--color-accent-soft); }
.type-icon { font-size: 2rem; }
.type-label { font-size: .95rem; font-weight: 700; color: var(--color-text); }
.type-desc { font-size: .75rem; color: var(--color-text-muted); }
.form { padding: 2rem; display: flex; flex-direction: column; gap: 1.25rem; }
.field { display: flex; flex-direction: column; gap: .35rem; }
.field-label { font-size: .8rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .04em; }
.optional { font-weight: 400; opacity: .6; text-transform: none; }
.req { color: var(--color-accent); }
.field-input { width: 100%; box-sizing: border-box; background: var(--color-surface-2); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: .7rem .9rem; color: var(--color-text); font-family: var(--font-body); font-size: .9rem; outline: none; transition: border-color .2s, box-shadow .2s; resize: vertical; }
.field-input:focus { border-color: var(--color-accent); box-shadow: 0 0 0 3px var(--color-accent-soft); }
.field-ta { min-height: 72px; }
.cat-grid { display: flex; flex-wrap: wrap; gap: .4rem; }
.cat-btn { padding: .35rem .8rem; border: 1px solid var(--color-border); border-radius: 999px; background: none; color: var(--color-text-muted); font-size: .8rem; cursor: pointer; transition: all .15s; font-family: var(--font-body); }
.cat-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.cat-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
.vs-row { display: flex; align-items: center; gap: 1rem; }
.vs-line { flex: 1; height: 2px; background: var(--color-border); }
.vs-text { font-size: .7rem; font-weight: 900; letter-spacing: .1em; color: var(--color-text-faint); white-space: nowrap; }
.options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.opt-card { background: var(--color-surface-2); border: 2px solid var(--color-border); border-radius: var(--radius-lg); padding: 1.25rem; display: flex; flex-direction: column; gap: .75rem; }
.opt-a { border-color: rgba(99,102,241,.3); }
.opt-b { border-color: rgba(236,72,153,.3); }
.opt-letter { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; }
.opt-a .opt-letter { background: rgba(99,102,241,.2); color: #6366f1; }
.opt-b .opt-letter { background: rgba(236,72,153,.2); color: #ec4899; }
.file-drop { border: 2px dashed var(--color-border); border-radius: var(--radius-md); padding: .75rem; cursor: pointer; display: flex; align-items: center; justify-content: center; min-height: 60px; transition: border-color .2s, background .2s; position: relative; }
.file-drop:hover { border-color: var(--color-accent); background: var(--color-accent-soft); }
.file-drop-sm { min-height: 44px; }
.file-ph { font-size: .78rem; color: var(--color-text-faint); }
.preview-img { width: 100%; height: 80px; object-fit: cover; border-radius: var(--radius-sm); }
.audio-name { font-size: .78rem; color: var(--color-text-muted); }
.hidden { display: none; }
.toggle-row { display: flex; align-items: center; gap: .75rem; cursor: pointer; font-size: .9rem; }
.toggle-track { width: 44px; height: 24px; background: var(--color-border); border-radius: 12px; position: relative; transition: background .2s; cursor: pointer; }
.toggle-track.active { background: var(--color-accent); }
.toggle-thumb { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; background: white; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
.toggle-track.active .toggle-thumb { transform: translateX(20px); }
.form-actions { display: flex; justify-content: flex-end; gap: .75rem; padding-top: .5rem; border-top: 1px solid var(--color-border); }
.btn-sm { padding: .3rem .75rem; font-size: .8rem; }
.group-questions { display: flex; flex-direction: column; gap: .75rem; }
.gq-header { display: flex; align-items: center; justify-content: space-between; }
.gq-title { font-size: .875rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .05em; }
.gq-item { display: flex; gap: .75rem; align-items: flex-start; background: var(--color-surface-2); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1rem; }
.gq-num { width: 28px; height: 28px; border-radius: 50%; background: var(--color-accent-soft); color: var(--color-accent); font-weight: 800; font-size: .875rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: .2rem; }
.gq-content { flex: 1; display: flex; flex-direction: column; gap: .6rem; }
.options-grid-sm { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
.opt-sm { display: flex; align-items: center; gap: .4rem; background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius-sm); padding: .5rem .6rem; }
.opt-sm.opt-a { border-color: rgba(99,102,241,.3); }
.opt-sm.opt-b { border-color: rgba(236,72,153,.3); }
.opt-letter-sm { width: 22px; height: 22px; border-radius: 50%; font-size: .7rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.opt-sm.opt-a .opt-letter-sm { background: rgba(99,102,241,.15); color: #6366f1; }
.opt-sm.opt-b .opt-letter-sm { background: rgba(236,72,153,.15); color: #ec4899; }
.opt-sm .field-input { flex: 1; padding: .3rem .5rem; font-size: .82rem; min-width: 0; }
.img-upload { cursor: pointer; flex-shrink: 0; }
.mini-img { width: 28px; height: 28px; object-fit: cover; border-radius: 4px; }
.mini-ph { font-size: 1.1rem; opacity: .4; }
.rm-btn { background: none; border: none; color: var(--color-text-faint); cursor: pointer; font-size: .875rem; padding: .25rem; flex-shrink: 0; }
.rm-btn:hover { color: #ef4444; }
.order-btns { display: flex; gap: .5rem; flex-wrap: wrap; }
.order-btn { padding: .5rem 1rem; border: 1.5px solid var(--color-border); border-radius: var(--radius-md); background: none; color: var(--color-text-muted); font-family: var(--font-body); font-size: .875rem; cursor: pointer; transition: all .15s; }
.order-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.order-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }
.elim-section { display: flex; flex-direction: column; gap: .6rem; }
.elim-header { display: flex; align-items: center; gap: .75rem; }
.elim-header .font-display { font-weight: 700; font-size: .875rem; text-transform: uppercase; letter-spacing: .05em; color: var(--color-text-muted); }
.elim-hint { font-size: .72rem; color: var(--color-text-faint); flex: 1; }
.elim-list { display: flex; flex-direction: column; gap: .4rem; }
.elim-row { display: flex; align-items: center; gap: .5rem; }
.elim-num { width: 24px; text-align: center; font-size: .75rem; color: var(--color-text-faint); font-weight: 600; flex-shrink: 0; }
.elim-row .field-input { flex: 1; padding: .5rem .75rem; }
@media (max-width: 600px) { .type-selector { grid-template-columns: 1fr; } .options-grid, .options-grid-sm { grid-template-columns: 1fr; } .form { padding: 1.25rem; } }
</style>
