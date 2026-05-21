<template>
  <AppLayout>
    <div class="edit-layout">
      <div class="edit-header">
        <Link :href="route('profile.show')" class="back-link">{{ t("common.back") }}</Link>
        <h1 class="font-display edit-title">{{ t("profile.edit_title") }}</h1>
      </div>

      <form class="edit-form card" @submit.prevent="submit" enctype="multipart/form-data">

        <!-- Avatar -->
        <div class="avatar-section">
          <div class="avatar-preview-wrap">
            <img :src="avatarPreview || profileUser.avatar_url" class="avatar-preview" />
            <button type="button" class="avatar-change-btn" @click="$refs.avatarInput.click()">📷</button>
          </div>
          <div>
            <p class="avatar-label font-display">{{ t("profile.avatar") }}</p>
            <p class="avatar-hint">{{ t("profile.avatar_hint") }}</p>
          </div>
          <input ref="avatarInput" type="file" accept="image/*" class="file-hidden" @change="onAvatarChange" />
        </div>

        <div class="fields-grid">
          <div class="field">
            <label class="field-label">{{ t("profile.fullname") }} <span class="required">*</span></label>
            <input v-model="form.name" type="text" class="field-input" required maxlength="255" />
            <p v-if="errors.name" class="field-error">{{ errors.name }}</p>
          </div>

          <div class="field">
            <label class="field-label">{{ t("auth.username") }} <span class="required">*</span></label>
            <div class="input-prefix-wrap">
              <span class="input-prefix">@</span>
              <input v-model="form.username" type="text" class="field-input input-with-prefix" required maxlength="30" />
            </div>
            <p v-if="errors.username" class="field-error">{{ errors.username }}</p>
          </div>
        </div>

        <div class="field">
          <label class="field-label">Bio <span class="optional">(optionnel)</span></label>
          <textarea v-model="form.bio" class="field-input field-textarea" rows="3" maxlength="160" :placeholder="t('profile.bio_placeholder')" />
          <p class="char-count">{{ form.bio?.length || 0 }} / 160</p>
          <p v-if="errors.bio" class="field-error">{{ errors.bio }}</p>
        </div>

        <div class="form-actions">
          <Link :href="route('profile.show')" class="btn-ghost">{{ t("create.cancel") }}</Link>
          <button type="submit" class="btn-primary" :disabled="loading">
            {{ loading ? t('common.loading') : '💾 Sauvegarder' }}
          </button>
        </div>
      </form>

      <!-- Danger zone -->
      <div class="danger-zone card">
        <h3 class="font-display danger-title">{{ t("profile.danger_zone") }}</h3>
        <p class="danger-text">La suppression de ton compte est irréversible. Toutes tes données seront perdues.</p>
        <button class="btn-danger" @click="confirmDelete">{{ t("profile.delete_account") }}</button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useI18n } from '@/Composables/useI18n'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const { t } = useI18n()
const props = defineProps({ profileUser: Object })

const loading = ref(false)
const errors = ref({})
const avatarPreview = ref(null)
const avatarFile = ref(null)

const form = reactive({
  name: props.profileUser.name,
  username: props.profileUser.username,
  bio: props.profileUser.bio || '',
})

const onAvatarChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

const submit = () => {
  loading.value = true
  errors.value = {}

  const data = new FormData()
  data.append('name', form.name)
  data.append('username', form.username)
  data.append('bio', form.bio)
  data.append('_method', 'PATCH')
  if (avatarFile.value) data.append('avatar', avatarFile.value)

  router.post(route('profile.update'), data, {
    onError: (e) => { errors.value = e },
    onFinish: () => { loading.value = false },
  })
}

const confirmDelete = () => {
  if (confirm('Es-tu sûr de vouloir supprimer définitivement ton compte ? Cette action est irréversible.')) {
    router.delete(route('profile.destroy'))
  }
}
</script>

<style scoped>
.edit-layout { max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.edit-header { display: flex; flex-direction: column; gap: .5rem; }
.back-link { color: var(--color-text-muted); text-decoration: none; font-size: .875rem; transition: color .2s; }
.back-link:hover { color: var(--color-accent); }
.edit-title { font-size: 1.5rem; font-weight: 800; margin: 0; }

.edit-form { padding: 2rem; display: flex; flex-direction: column; gap: 1.25rem; }

/* Avatar */
.avatar-section { display: flex; align-items: center; gap: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--color-border); }
.avatar-preview-wrap { position: relative; flex-shrink: 0; }
.avatar-preview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--color-border); }
.avatar-change-btn {
  position: absolute; bottom: 0; right: 0;
  width: 28px; height: 28px;
  background: var(--color-accent); border: none; border-radius: 50%;
  font-size: .8rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: transform .2s;
}
.avatar-change-btn:hover { transform: scale(1.1); }
.avatar-label { font-weight: 700; font-size: .9rem; margin: 0 0 .2rem; }
.avatar-hint { font-size: .75rem; color: var(--color-text-muted); margin: 0; }
.file-hidden { display: none; }

/* Fields */
.fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.field { display: flex; flex-direction: column; gap: .35rem; }
.field-label { font-size: .8rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .04em; }
.optional { font-weight: 400; opacity: .6; text-transform: none; }
.required { color: var(--color-accent); }
.field-input {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: .7rem .9rem;
  color: var(--color-text);
  font-family: var(--font-body);
  font-size: .9rem;
  outline: none;
  transition: border-color .2s, box-shadow .2s;
  resize: vertical;
}
.field-input:focus { border-color: var(--color-accent); box-shadow: 0 0 0 3px var(--color-accent-soft); }
.field-textarea { min-height: 80px; }
.field-error { color: #ef4444; font-size: .78rem; }
.char-count { font-size: .72rem; color: var(--color-text-faint); text-align: right; margin: 0; }

.input-prefix-wrap { position: relative; }
.input-prefix {
  position: absolute; left: .9rem; top: 50%; transform: translateY(-50%);
  color: var(--color-text-muted); font-size: .9rem; pointer-events: none;
}
.input-with-prefix { padding-left: 1.75rem !important; }

/* Actions */
.form-actions { display: flex; justify-content: flex-end; gap: .75rem; padding-top: .5rem; border-top: 1px solid var(--color-border); }

/* Danger zone */
.danger-zone { padding: 1.5rem; border-color: rgba(239,68,68,.3); }
.danger-title { font-size: 1rem; font-weight: 700; color: #ef4444; margin: 0 0 .5rem; }
.danger-text { font-size: .875rem; color: var(--color-text-muted); margin: 0 0 1rem; }
.btn-danger {
  padding: .5rem 1.25rem;
  background: rgba(239,68,68,.1);
  color: #ef4444;
  border: 1px solid rgba(239,68,68,.3);
  border-radius: var(--radius-md);
  font-family: var(--font-body);
  font-size: .875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
}
.btn-danger:hover { background: rgba(239,68,68,.2); }

@media (max-width: 480px) { .fields-grid { grid-template-columns: 1fr; } }
</style>
