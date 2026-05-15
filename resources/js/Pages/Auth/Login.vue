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

      <h1 class="font-display auth-title">Bon retour !</h1>
      <p class="auth-subtitle">Connecte-toi pour voter et créer tes dilemmes.</p>

      <form @submit.prevent="submit" class="auth-form">
        <div class="field">
          <label class="field-label">Email</label>
          <input v-model="form.email" type="email" class="field-input" placeholder="toi@example.com" required autocomplete="email" />
          <p v-if="errors.email" class="field-error">{{ errors.email }}</p>
        </div>

        <div class="field">
          <label class="field-label">Mot de passe</label>
          <input v-model="form.password" type="password" class="field-input" placeholder="••••••••" required autocomplete="current-password" />
          <p v-if="errors.password" class="field-error">{{ errors.password }}</p>
        </div>

        <label class="remember-label">
          <input v-model="form.remember" type="checkbox" />
          <span>Se souvenir de moi</span>
        </label>

        <button type="submit" class="btn-primary auth-btn" :disabled="loading">
          {{ loading ? 'Connexion...' : 'Se connecter' }}
        </button>
      </form>

      <p class="auth-switch">
        Pas encore de compte ?
        <Link :href="route('register')" class="auth-link">S'inscrire gratuitement</Link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useTheme } from '@/Composables/useTheme'

const { theme } = useTheme()
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
</style>
