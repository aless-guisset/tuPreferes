<template>
  <div class="auth-page" :class="theme">
    <div class="auth-bg">
      <div class="auth-blob blob-1" />
      <div class="auth-blob blob-2" />
    </div>
    <div class="auth-card card animate-scale-in">
      <Link :href="route('questions.index')" class="auth-logo">
        <span>🤔</span>
        <span class="font-display logo-text">TuPréfères</span>
      </Link>
      <h1 class="font-display auth-title">{{ t("auth.register_title") }} !</h1>
      <p class="auth-subtitle">{{ t("auth.register_sub") }}</p>
      <form @submit.prevent="submit" class="auth-form">
        <div class="field">
          <label class="field-label">{{ t("auth.name") }}</label>
          <input v-model="form.name" type="text" class="field-input" placeholder="Alice Dupont" required autocomplete="name" />
          <p v-if="errors.name" class="field-error">{{ errors.name }}</p>
        </div>
        <div class="field">
          <label class="field-label">{{ t("auth.username") }}</label>
          <input v-model="form.username" type="text" class="field-input" placeholder="alice42" required autocomplete="username" />
          <p v-if="errors.username" class="field-error">{{ errors.username }}</p>
        </div>
        <div class="field">
          <label class="field-label">{{ t("auth.email") }}</label>
          <input v-model="form.email" type="email" class="field-input" placeholder="alice@example.com" required autocomplete="email" />
          <p v-if="errors.email" class="field-error">{{ errors.email }}</p>
        </div>
        <div class="field">
          <label class="field-label">{{ t("auth.password") }}</label>
          <input v-model="form.password" type="password" class="field-input" placeholder="••••••••" required autocomplete="new-password" />
          <p v-if="errors.password" class="field-error">{{ errors.password }}</p>
        </div>
        <div class="field">
          <label class="field-label">{{ t("auth.password_confirm") }}</label>
          <input v-model="form.password_confirmation" type="password" class="field-input" placeholder="••••••••" required autocomplete="new-password" />
        </div>
        <button type="submit" class="btn-primary auth-btn" :disabled="loading">
          {{ loading ? t('common.loading') : t('auth.register_btn') }}
        </button>
      </form>
      <p class="auth-switch">
        {{ t("auth.has_account") }} ? <Link :href="route('login')" class="auth-link">{{ t("auth.login_link") }}</Link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useI18n } from '@/Composables/useI18n'
import { useI18n } from '@/Composables/useI18n'
import { Link, router } from '@inertiajs/vue3'
import { useTheme } from '@/Composables/useTheme'
const { theme } = useTheme()
const { t } = useI18n()
const { t } = useI18n()
const loading = ref(false)
const errors  = ref({})
const form = reactive({ name: '', username: '', email: '', password: '', password_confirmation: '' })
const submit = () => {
  loading.value = true
  errors.value  = {}
  router.post(route('register'), form, {
    onError:  (e) => { errors.value = e },
    onFinish: () => { loading.value = false },
  })
}
</script>

<style scoped>
.auth-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--color-bg); position: relative; overflow: hidden; padding: 1.5rem 1rem; }
.auth-bg { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.auth-blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: .18; }
.blob-1 { width: 400px; height: 400px; background: #ec4899; top: -100px; left: -100px; }
.blob-2 { width: 300px; height: 300px; background: var(--color-accent); bottom: -80px; right: -80px; }
.auth-card { width: 100%; max-width: 440px; padding: 2rem; position: relative; z-index: 1; box-sizing: border-box; }
.auth-logo { display: flex; align-items: center; gap: .5rem; text-decoration: none; margin-bottom: 1.5rem; }
.logo-text { font-size: 1.2rem; font-weight: 800; color: var(--color-accent); }
.auth-title { font-size: 1.5rem; font-weight: 800; margin: 0 0 .3rem; }
.auth-subtitle { color: var(--color-text-muted); font-size: .875rem; margin: 0 0 1.5rem; }
.auth-form { display: flex; flex-direction: column; gap: .9rem; }
.field { display: flex; flex-direction: column; gap: .3rem; }
.field-label { font-size: .78rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .04em; }
.field-input { width: 100%; box-sizing: border-box; background: var(--color-surface-2); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: .65rem .85rem; color: var(--color-text); font-family: var(--font-body); font-size: .9rem; outline: none; transition: border-color .2s, box-shadow .2s; }
.field-input:focus { border-color: var(--color-accent); box-shadow: 0 0 0 3px var(--color-accent-soft); }
.field-error { color: #ef4444; font-size: .75rem; margin: 0; }
.auth-btn { width: 100%; justify-content: center; padding: .75rem; font-size: .95rem; margin-top: .25rem; }
.auth-btn:disabled { opacity: .7; cursor: not-allowed; }
.auth-switch { text-align: center; color: var(--color-text-muted); font-size: .875rem; margin: 1.25rem 0 0; }
.auth-link { color: var(--color-accent); text-decoration: none; font-weight: 600; }
.auth-link:hover { text-decoration: underline; }
@media (max-width: 480px) { .auth-card { padding: 1.5rem 1.25rem; } }
</style>
