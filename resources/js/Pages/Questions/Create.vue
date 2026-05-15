<template>
  <AppLayout>
    <div class="create-layout">
      <div class="create-header">
        <h1 class="font-display create-title">Nouveau dilemme 🤔</h1>
        <p class="create-subtitle">Lance un "tu préfères" et vois ce que tout le monde choisit !</p>
      </div>

      <form class="create-form card" @submit.prevent="submit">

        <!-- Title (optional) -->
        <div class="field">
          <label class="field-label">Titre <span class="optional">(optionnel)</span></label>
          <input
            v-model="form.title"
            type="text"
            class="field-input"
            placeholder="Ex: Le plus grand dilemme de l'humanité..."
            maxlength="255"
          />
          <p v-if="errors.title" class="field-error">{{ errors.title }}</p>
        </div>

        <!-- Category -->
        <div class="field">
          <label class="field-label">Catégorie <span class="required">*</span></label>
          <div class="cat-grid">
            <button
              v-for="cat in categories"
              :key="cat.value"
              type="button"
              class="cat-select-btn"
              :class="{ active: form.category === cat.value }"
              @click="form.category = cat.value"
            >{{ cat.emoji }} {{ cat.label }}</button>
          </div>
          <p v-if="errors.category" class="field-error">{{ errors.category }}</p>
        </div>

        <!-- Options -->
        <div class="options-section">
          <div class="vs-label-big">
            <div class="vs-line-big" />
            <span class="vs-text-big font-display">TU PRÉFÈRES...</span>
            <div class="vs-line-big" />
          </div>

          <div class="options-grid">
            <div
              v-for="(option, idx) in form.options"
              :key="idx"
              class="option-form-card"
              :class="idx === 0 ? 'opt-a' : 'opt-b'"
            >
              <div class="option-header">
                <span class="option-letter font-display">{{ idx === 0 ? 'A' : 'B' }}</span>
              </div>

              <!-- Texte -->
              <div class="field">
                <label class="field-label">Texte <span class="required">*</span></label>
                <textarea
                  v-model="option.label"
                  class="field-input field-textarea"
                  :placeholder="idx === 0 ? 'Ex: Voyager dans le temps...' : 'Ex: Avoir des superpouvoirs...'"
                  rows="2"
                  maxlength="255"
                  required
                />
                <p v-if="errors[`options.${idx}.label`]" class="field-error">{{ errors[`options.${idx}.label`] }}</p>
              </div>

              <!-- Image -->
              <div class="field">
                <label class="field-label">Image <span class="optional">(optionnel)</span></label>
                <div class="file-drop" @click="$refs[`img${idx}`][0].click()" @dragover.prevent @drop.prevent="onDrop($event, idx, 'image')">
                  <div v-if="option.imagePreview" class="file-preview">
                    <img :src="option.imagePreview" class="preview-img" />
                    <button type="button" class="remove-file" @click.stop="removeFile(idx, 'image')">✕</button>
                  </div>
                  <div v-else class="file-placeholder">
                    <span class="file-icon">🖼️</span>
                    <span>Glisser ou cliquer pour ajouter une image</span>
                    <span class="file-hint">PNG, JPG · max 5 Mo</span>
                  </div>
                  <input
                    :ref="el => { if (el) imgRefs[idx] = el }"
                    type="file"
                    accept="image/*"
                    class="file-hidden"
                    @change="onFileChange($event, idx, 'image')"
                  />
                </div>
                <p v-if="errors[`options.${idx}.image`]" class="field-error">{{ errors[`options.${idx}.image`] }}</p>
              </div>

              <!-- Audio -->
              <div class="field">
                <label class="field-label">Audio <span class="optional">(optionnel)</span></label>
                <div class="file-drop file-drop-audio" @click="audioRefs[idx]?.click()" @dragover.prevent @drop.prevent="onDrop($event, idx, 'audio')">
                  <div v-if="option.audioName" class="audio-preview">
                    <span>🎵 {{ option.audioName }}</span>
                    <button type="button" class="remove-file" @click.stop="removeFile(idx, 'audio')">✕</button>
                  </div>
                  <div v-else class="file-placeholder">
                    <span class="file-icon">🎵</span>
                    <span>Ajouter un audio</span>
                    <span class="file-hint">MP3, WAV, OGG · max 10 Mo</span>
                  </div>
                  <input
                    :ref="el => { if (el) audioRefs[idx] = el }"
                    type="file"
                    accept=".mp3,.wav,.ogg,.m4a"
                    class="file-hidden"
                    @change="onFileChange($event, idx, 'audio')"
                  />
                </div>
                <p v-if="errors[`options.${idx}.audio`]" class="field-error">{{ errors[`options.${idx}.audio`] }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Anonymous -->
        <div class="field field-inline">
          <label class="toggle-label">
            <div class="toggle-wrap">
              <input v-model="form.is_anonymous" type="checkbox" class="toggle-input" />
              <div class="toggle-track"><div class="toggle-thumb" /></div>
            </div>
            <span>Publier anonymement</span>
          </label>
        </div>

        <!-- Submit -->
        <div class="form-actions">
          <Link :href="route('questions.index')" class="btn-ghost">Annuler</Link>
          <button type="submit" class="btn-primary" :disabled="loading">
            <span v-if="loading">⏳ Création...</span>
            <span v-else>🚀 Publier le dilemme</span>
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useToast } from '@/Composables/useToast'

const { add: toast } = useToast()
const loading = ref(false)
const errors = ref({})

const imgRefs = ref([])
const audioRefs = ref([])

const form = reactive({
  title: '',
  category: 'divers',
  is_anonymous: false,
  options: [
    { label: '', image: null, imagePreview: null, audio: null, audioName: null },
    { label: '', image: null, imagePreview: null, audio: null, audioName: null },
  ],
})

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

const onFileChange = (e, idx, type) => {
  const file = e.target.files[0]
  if (!file) return
  if (type === 'image') {
    form.options[idx].image = file
    form.options[idx].imagePreview = URL.createObjectURL(file)
  } else {
    form.options[idx].audio = file
    form.options[idx].audioName = file.name
  }
}

const onDrop = (e, idx, type) => {
  const file = e.dataTransfer.files[0]
  if (!file) return
  const fakeEvent = { target: { files: [file] } }
  onFileChange(fakeEvent, idx, type)
}

const removeFile = (idx, type) => {
  if (type === 'image') {
    form.options[idx].image = null
    form.options[idx].imagePreview = null
  } else {
    form.options[idx].audio = null
    form.options[idx].audioName = null
  }
}

const submit = () => {
  loading.value = true
  errors.value = {}

  const data = new FormData()
  if (form.title) data.append('title', form.title)
  data.append('category', form.category)
  data.append('is_anonymous', form.is_anonymous ? '1' : '0')

  form.options.forEach((opt, i) => {
    data.append(`options[${i}][label]`, opt.label)
    if (opt.image) data.append(`options[${i}][image]`, opt.image)
    if (opt.audio) data.append(`options[${i}][audio]`, opt.audio)
  })

  router.post(route('questions.store'), data, {
    onError: (e) => { errors.value = e; toast('Corrige les erreurs du formulaire.', 'error') },
    onFinish: () => { loading.value = false },
  })
}
</script>

<style scoped>
.create-layout { max-width: 760px; margin: 0 auto; }
.create-header { margin-bottom: 1.5rem; }
.create-title { font-size: 1.75rem; font-weight: 800; margin: 0 0 .4rem; }
.create-subtitle { color: var(--color-text-muted); margin: 0; }

.create-form { padding: 2rem; display: flex; flex-direction: column; gap: 1.5rem; }

.field { display: flex; flex-direction: column; gap: .4rem; }
.field-label { font-size: .825rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .04em; }
.optional { font-weight: 400; opacity: .6; text-transform: none; }
.required { color: var(--color-accent); }
.field-input {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: .65rem .9rem;
  color: var(--color-text);
  font-family: var(--font-body);
  font-size: .9rem;
  outline: none;
  transition: border-color .2s, box-shadow .2s;
  resize: vertical;
}
.field-input:focus {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}
.field-textarea { min-height: 72px; }
.field-error { color: #ef4444; font-size: .78rem; }

/* Category grid */
.cat-grid { display: flex; flex-wrap: wrap; gap: .5rem; }
.cat-select-btn {
  padding: .4rem .85rem;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  background: none;
  color: var(--color-text-muted);
  font-size: .825rem;
  cursor: pointer;
  transition: all .15s;
  font-family: var(--font-body);
}
.cat-select-btn:hover { border-color: var(--color-accent); color: var(--color-accent); }
.cat-select-btn.active { background: var(--color-accent); color: white; border-color: var(--color-accent); }

/* Options */
.vs-label-big { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem; }
.vs-line-big { flex: 1; height: 2px; background: linear-gradient(to right, transparent, var(--color-border)); }
.vs-line-big:last-child { background: linear-gradient(to left, transparent, var(--color-border)); }
.vs-text-big { font-size: .75rem; font-weight: 900; letter-spacing: .1em; color: var(--color-text-faint); white-space: nowrap; }

.options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.option-form-card {
  background: var(--color-surface-2);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.opt-a { border-color: rgba(99,102,241,.3); }
.opt-b { border-color: rgba(236,72,153,.3); }
.option-header { display: flex; align-items: center; gap: .5rem; }
.option-letter {
  width: 32px; height: 32px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 1rem;
}
.opt-a .option-letter { background: rgba(99,102,241,.2); color: #6366f1; }
.opt-b .option-letter { background: rgba(236,72,153,.2); color: #ec4899; }

/* File drop */
.file-drop {
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-md);
  padding: 1rem;
  cursor: pointer;
  position: relative;
  transition: border-color .2s, background .2s;
  min-height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.file-drop:hover { border-color: var(--color-accent); background: var(--color-accent-soft); }
.file-hidden { display: none; }
.file-placeholder { display: flex; flex-direction: column; align-items: center; gap: .3rem; text-align: center; }
.file-icon { font-size: 1.5rem; }
.file-placeholder span { font-size: .78rem; color: var(--color-text-muted); }
.file-hint { font-size: .7rem; color: var(--color-text-faint) !important; }

.file-preview { position: relative; width: 100%; }
.preview-img { width: 100%; height: 120px; object-fit: cover; border-radius: var(--radius-sm); }
.audio-preview { display: flex; align-items: center; justify-content: space-between; width: 100%; font-size: .8rem; color: var(--color-text-muted); }
.remove-file {
  position: absolute; top: -.5rem; right: -.5rem;
  background: #ef4444; color: white;
  border: none; border-radius: 50%;
  width: 22px; height: 22px;
  font-size: .65rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.audio-preview .remove-file { position: static; flex-shrink: 0; }

/* Toggle */
.field-inline { flex-direction: row; align-items: center; }
.toggle-label { display: flex; align-items: center; gap: .75rem; cursor: pointer; font-size: .9rem; user-select: none; }
.toggle-input { display: none; }
.toggle-track {
  width: 44px; height: 24px;
  background: var(--color-border);
  border-radius: 12px;
  position: relative;
  transition: background .2s;
}
.toggle-input:checked ~ * .toggle-track,
.toggle-label:has(input:checked) .toggle-track { background: var(--color-accent); }
.toggle-thumb {
  position: absolute;
  top: 3px; left: 3px;
  width: 18px; height: 18px;
  background: white;
  border-radius: 50%;
  transition: transform .2s;
  box-shadow: 0 1px 3px rgba(0,0,0,.3);
}
.toggle-input:checked + .toggle-track .toggle-thumb { transform: translateX(20px); }

/* Form actions */
.form-actions { display: flex; justify-content: flex-end; gap: .75rem; padding-top: .5rem; border-top: 1px solid var(--color-border); }

/* Responsive */
@media (max-width: 600px) {
  .create-form { padding: 1.25rem; }
  .options-grid { grid-template-columns: 1fr; }
}
</style>
