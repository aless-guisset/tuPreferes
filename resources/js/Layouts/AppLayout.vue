<template>
  <div :class="theme" style="min-height:100vh; background:var(--color-bg); color:var(--color-text);">

    <!-- ── Desktop Navbar ─────────────────────────────────────────────────── -->
    <header class="desktop-nav">
      <div class="nav-inner">
        <!-- Logo -->
        <Link :href="route('questions.index')" class="logo">
          <span class="logo-icon">🤔</span>
          <span class="font-display logo-text">{{ t('app.name') }}</span>
        </Link>

        
      <!-- Search -->
        <div class="nav-search">
          <SearchBar />
        </div>

        <!-- Right actions -->
        <div class="nav-actions">
          <LocaleSwitcher />
          <button class="btn-ghost icon-btn" @click="toggleTheme" :title="theme === 'dark' ? 'Mode clair' : 'Mode sombre'">
            <SunIcon v-if="theme === 'dark'" />
            <MoonIcon v-else />
          </button>

          <!-- Télécharger l'app -->
          <a href="/downloads/tuPreferes.apk" download class="btn-download" title="Télécharger l'app Android">
            📲 <span class="download-label">App</span>
          </a>

          <template v-if="$page.props.auth.user">
            <Link v-if="$page.props.auth.user.is_admin" :href="route('admin.index')" class="btn-ghost admin-btn">🛡️ Admin</Link>
            <Link :href="route('groups.create')" class="btn-primary">
              <PlusIcon /> Créer
            </Link>
            <Link :href="route('profile.show')" class="avatar-btn">
              <img :src="$page.props.auth.user.avatar_url" :alt="$page.props.auth.user.name" class="avatar-img" />
            </Link>
          </template>

          <template v-else>
            <Link :href="route('login')" class="btn-ghost">{{ t('nav.login') }}</Link>
            <Link :href="route('register')" class="btn-primary">{{ t('nav.register') }}</Link>
          </template>
        </div>
      </div>
    </header>

    <!-- ── Main content ───────────────────────────────────────────────────── -->
    <main class="main-content">
      <slot />
    </main>

    <!-- ── Mobile Bottom Nav ─────────────────────────────────────────────── -->
    <nav class="mobile-nav pb-safe">
      <template v-if="$page.props.auth.user">
            <Link v-if="$page.props.auth.user.is_admin" :href="route('admin.index')" class="btn-ghost admin-btn">🛡️ Admin</Link>
        <!-- Accueil -->
        <Link :href="route('questions.index')" class="mobile-nav-btn" :class="{ active: isRoute('questions.index') }">
          <HomeIcon />
          <span>{{ t('nav.home') }}</span>
        </Link>

        <!-- Recherche -->
        <Link :href="route('questions.index') + '?focus=search'" class="mobile-nav-btn" :class="{ active: isRoute('questions.index') && focusSearch }">
          <SearchIcon />
          <span>{{ t('nav.explore') }}</span>
        </Link>

        <!-- Créer (centre, accent) -->
        <Link :href="route('groups.create')" class="mobile-nav-btn mobile-nav-create">
          <div class="create-bubble">
            <PlusIcon />
          </div>
        </Link>

        <!-- Historique -->
        <Link :href="route('groups.index')" class="mobile-nav-btn" :class="{ active: isRoute('groups.index') }">
          <span style="font-size:1.2rem">📦</span>
          <span>{{ t('nav.groups') }}</span>
        </Link>

        <!-- Profil -->
        <Link v-if="$page.props.auth.user.is_admin" :href="route('admin.index')" class="mobile-nav-btn" :class="{ active: isRoute('admin.index') }">
          <span style="font-size:1.2rem">🛡️</span>
          <span>Admin</span>
        </Link>
        <Link :href="route('profile.show')" class="mobile-nav-btn" :class="{ active: isRoute('profile.show') }">
          <img :src="$page.props.auth.user.avatar_url" class="mobile-avatar" />
          <span>{{ t('nav.profile') }}</span>
        </Link>
        <a href="/downloads/tuPreferes.apk" download class="mobile-nav-btn">
          <span style="font-size:1.2rem">📲</span>
          <span>App</span>
        </a>
      </template>

      <template v-else>
        <!-- Non connecté : Recherche + Connexion -->
        <Link :href="route('questions.index')" class="mobile-nav-btn" :class="{ active: isRoute('questions.index') }">
          <HomeIcon />
          <span>{{ t('nav.home') }}</span>
        </Link>
        <div class="mobile-nav-spacer" />
        <Link :href="route('login')" class="mobile-nav-btn mobile-nav-create">
          <div class="create-bubble">
            <UserIcon />
          </div>
        </Link>
        <div class="mobile-nav-spacer" />
        <button class="mobile-nav-btn" @click="toggleTheme">
          <SunIcon v-if="theme === 'dark'" />
          <MoonIcon v-else />
          <span>{{ t('nav.theme_dark') }}</span>
        </button>
        <a href="/downloads/tuPreferes.apk" download class="mobile-nav-btn">
          <span style="font-size:1.2rem">📲</span>
          <span>App</span>
        </a>
      </template>
    </nav>

    <!-- ── Toast notifications ────────────────────────────────────────────── -->
    <ToastContainer />

    <!-- ── Flash messages ─────────────────────────────────────────────────── -->
    <FlashHandler />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue'
import { useI18n } from '@/Composables/useI18n'
import { useTheme } from '@/Composables/useTheme'
import SearchBar from '@/Components/SearchBar.vue'
import ToastContainer from '@/Components/ToastContainer.vue'
import FlashHandler from '@/Components/FlashHandler.vue'

// Icons (inline SVG components)
import HomeIcon from '@/Components/Icons/HomeIcon.vue'
import SearchIcon from '@/Components/Icons/SearchIcon.vue'
import PlusIcon from '@/Components/Icons/PlusIcon.vue'
import ClockIcon from '@/Components/Icons/ClockIcon.vue'
import UserIcon from '@/Components/Icons/UserIcon.vue'
import SunIcon from '@/Components/Icons/SunIcon.vue'
import MoonIcon from '@/Components/Icons/MoonIcon.vue'

const { theme, toggle: toggleTheme } = useTheme()
const { t } = useI18n()
const page = usePage()

const isRoute = (name) => route().current(name)
const focusSearch = computed(() => new URLSearchParams(window.location.search).has('focus'))
</script>

<style scoped>
/* ── Desktop Navbar ──────────────────────────────────────────────────────── */
.desktop-nav {
  position: sticky;
  top: 0;
  z-index: 100;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  backdrop-filter: blur(12px);
}
.nav-inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 1.5rem;
  height: 64px;
  display: flex;
  align-items: center;
  gap: 1.5rem;
}
.logo {
  display: flex;
  align-items: center;
  gap: .5rem;
  text-decoration: none;
  flex-shrink: 0;
}
.logo-icon { font-size: 1.5rem; }
.logo-text {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-accent);
  letter-spacing: -.02em;
}
.nav-search { flex: 1; max-width: 480px; }
.nav-link { color: var(--color-text-muted); text-decoration: none; font-size: .875rem; font-weight: 500; padding: .4rem .75rem; border-radius: var(--radius-sm); transition: color .2s, background .2s; white-space: nowrap; }
.nav-link:hover { color: var(--color-accent); background: var(--color-accent-soft); }
.nav-actions {
  display: flex;
  align-items: center;
  gap: .75rem;
  margin-left: auto;
  flex-shrink: 0;
}
.icon-btn {
  padding: .5rem;
  width: 40px;
  height: 40px;
  justify-content: center;
}
.avatar-btn {
  display: block;
  border-radius: 50%;
  overflow: hidden;
  width: 38px;
  height: 38px;
  border: 2px solid var(--color-border);
  transition: border-color .2s;
}
.avatar-btn:hover { border-color: var(--color-accent); }
.btn-download { display: flex; align-items: center; gap: .35rem; padding: .4rem .85rem; border: 1.5px solid var(--color-accent); border-radius: var(--radius-md); color: var(--color-accent); font-size: .8rem; font-weight: 600; text-decoration: none; transition: all .2s; white-space: nowrap; }
.btn-download:hover { background: var(--color-accent); color: white; }
.download-label { display: none; }
@media (min-width: 900px) { .download-label { display: inline; } }
.avatar-img { width: 100%; height: 100%; object-fit: cover; }

/* ── Main content ────────────────────────────────────────────────────────── */
.main-content {
  max-width: 1280px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  /* Room for mobile nav */
  padding-bottom: calc(80px + env(safe-area-inset-bottom, 1rem));
}

/* ── Mobile Bottom Nav ───────────────────────────────────────────────────── */
.mobile-nav {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background: var(--color-surface);
  border-top: 1px solid var(--color-border);
  flex-direction: row;
  align-items: center;
  justify-content: space-around;
  padding: .5rem 1rem;
  gap: .25rem;
}
.mobile-nav-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .2rem;
  padding: .4rem .6rem;
  border-radius: var(--radius-sm);
  color: var(--color-text-muted);
  text-decoration: none;
  font-size: .65rem;
  font-weight: 500;
  cursor: pointer;
  background: none;
  border: none;
  transition: color .2s;
  min-width: 48px;
}
.mobile-nav-btn:hover,
.mobile-nav-btn.active { color: var(--color-accent); }
.mobile-nav-btn svg { width: 22px; height: 22px; }

.mobile-nav-create { color: transparent; }
.create-bubble {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: var(--color-accent);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-accent);
  color: white;
  transition: transform .2s;
  margin-top: -18px;
}
.mobile-nav-create:hover .create-bubble { transform: scale(1.08); }
.create-bubble svg { width: 24px; height: 24px; }

.mobile-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  object-fit: cover;
  border: 1.5px solid var(--color-border);
}
.mobile-nav-spacer { flex: 1; }

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
  .desktop-nav { display: none; }
  .mobile-nav  { display: flex; }
  .main-content { padding: 1rem .75rem; }
}
</style>
<!-- patch groupes -->
