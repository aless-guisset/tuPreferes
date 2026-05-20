<template>
  <div class="auth-page" :class="theme">
    <div class="auth-bg">
      <div class="auth-blob blob-1" />
      <div class="auth-blob blob-2" />
    </div>

    <div class="auth-card card animate-scale-in">
      <!-- Logo -->
      <Link :href="route('questions.index')" class="auth-logo">
        <span>🤔</span>
        <span class="font-display logo-text">TuPréfères</span>
      </Link>

      <h1 class="font-display auth-title">{{ t("auth.login_title") }} !</h1>
      <p class="auth-subtitle">{{ t('auth.login_sub') }}</p>

      <form @submit.prevent="submit" class="auth-form">
        <div class="field">
          <label class="field-label">{{ t('auth.email') }}</label>
          <input v-model="form.email" type="email" class="field-input" placeholder="toi@example.com" required autocomplete="email" />
          <p v-if="errors.email" class="field-error">{{ errors.email }}</p>
        </div>

        <div class="field">
          <label class="field-label">{{ t('auth.password') }}</label>
          <input v-model="form.password" type="password" class="field-input" placeholder="••••••••" required autocomplete="current-password" />
          <p v-if="errors.password" class="field-error">{{ errors.password }}</p>
        </div>

        <label class="remember-label">
          <input v-model="form.remember" type="checkbox" />
          <span>{{ t('auth.remember') }}</span>
        </label>

        <button type="submit" class="btn-primary auth-btn" :disabled="loading">
          {{ loading ? t('common.loading') : t('auth.login_btn') }}
        </button>
      </form>

      
      <!-- Séparateur -->
      <div class="oauth-sep">
        <div class="sep-line" /><span class="sep-text">{{ t('auth.or_continue') }}</span><div class="sep-line" />
      </div>

      <!-- Boutons OAuth -->
      <div class="oauth-btns">
        <a :href="route('social.redirect', 'google')" class="oauth-btn oauth-google">
          <svg viewBox="0 0 24 24" class="oauth-icon"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
          {{ t('auth.google') }}
        </a>
        <a :href="route('social.redirect', 'apple')" class="oauth-btn oauth-apple">
          <svg viewBox="0 0 24 24" class="oauth-icon"><path fill="currentColor" d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
          {{ t('auth.apple') }}
        </a>
      </div>

      <p class="auth-switch">
        {{ t('auth.no_account') }}
        <Link :href="route('register')" class="auth-link">{{ t('auth.register_link') }}</Link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useI18n } from '@/Composables/useI18n'  // ← keep only one
import { Link, router } from '@inertiajs/vue3'
import { useTheme } from '@/Composables/useTheme'

const { theme } = useTheme()
const { t } = useI18n()
const loading = ref(false)
const errors = ref({})

const form = reactive({ email: '', password: '', remember: false })

const submit = () => {
  loading.value = true
  errors.value = {}
  router.post(route('login'), form, {
    onError: (e) => { errors.value = e },
    onFinish: () => { loading.value = false },
  })
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg);
  position: relative;
  overflow: hidden;
  padding: 1rem;
}
.auth-bg { position: absolute; inset: 0; pointer-events: none; }
.auth-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: .18;
}
.blob-1 { width: 400px; height: 400px; background: var(--color-accent); top: -100px; right: -100px; }
.blob-2 { width: 300px; height: 300px; background: #6366f1; bottom: -80px; left: -80px; }

.auth-card {
  width: 100%;
  max-width: 420px;
  padding: 2.5rem;
  position: relative;
  z-index: 1;
}
.auth-logo { display: flex; align-items: center; gap: .5rem; text-decoration: none; margin-bottom: 1.5rem; }
.logo-text { font-size: 1.2rem; font-weight: 800; color: var(--color-accent); }
.auth-title { font-size: 1.6rem; font-weight: 800; margin: 0 0 .3rem; }
.auth-subtitle { color: var(--color-text-muted); font-size: .9rem; margin: 0 0 1.5rem; }

.auth-form { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: .35rem; }
.field-label { font-size: .8rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: .04em; }
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
}
.field-input:focus { border-color: var(--color-accent); box-shadow: 0 0 0 3px var(--color-accent-soft); }
.field-error { color: #ef4444; font-size: .78rem; }

.remember-label { display: flex; align-items: center; gap: .5rem; font-size: .85rem; color: var(--color-text-muted); cursor: pointer; }
.auth-btn { width: 100%; justify-content: center; padding: .8rem; font-size: .95rem; margin-top: .25rem; }

.auth-switch { text-align: center; color: var(--color-text-muted); font-size: .875rem; margin: 1.25rem 0 0; }
.auth-link { color: var(--color-accent); text-decoration: none; font-weight: 600; }
.auth-link:hover { text-decoration: underline; }

.oauth-sep { display:flex;align-items:center;gap:.75rem;margin:.5rem 0; }
.sep-line { flex:1;height:1px;background:var(--color-border); }
.sep-text { font-size:.78rem;color:var(--color-text-faint);white-space:nowrap; }
.oauth-btns { display:flex;flex-direction:column;gap:.6rem; }
.oauth-btn { display:flex;align-items:center;justify-content:center;gap:.75rem;padding:.7rem 1rem;border:1px solid var(--color-border);border-radius:var(--radius-md);text-decoration:none;font-family:var(--font-body);font-size:.9rem;font-weight:500;transition:all .2s;color:var(--color-text); }
.oauth-btn:hover { background:var(--color-surface-2);border-color:var(--color-accent); }
.oauth-icon { width:20px;height:20px;flex-shrink:0; }
.oauth-google { background:var(--color-surface); }
.oauth-apple  { background:var(--color-surface); }
</style>
