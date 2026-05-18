<template>
  <div class="locale-switcher" ref="switcherRef">
    <button class="locale-btn" @click="open = !open">
      <span>{{ current.flag }}</span>
      <span class="locale-code">{{ current.code.toUpperCase() }}</span>
      <span class="locale-arrow" :class="{ rotated: open }">▾</span>
    </button>

    <Transition name="dropdown">
      <div v-if="open" class="locale-dropdown">
        <button
          v-for="loc in availableLocales"
          :key="loc.code"
          class="locale-option"
          :class="{ active: locale === loc.code }"
          @click="select(loc.code)"
        >
          <span>{{ loc.flag }}</span>
          <span>{{ loc.label }}</span>
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { useI18n } from '@/Composables/useI18n'

const { locale, setLocale, availableLocales } = useI18n()
const open       = ref(false)
const switcherRef = ref(null)

const current = computed(() => availableLocales.find(l => l.code === locale.value) ?? availableLocales[0])

const select = (code) => {
  setLocale(code)
  open.value = false
}

onClickOutside(switcherRef, () => { open.value = false })
</script>

<style scoped>
.locale-switcher { position: relative; }
.locale-btn {
  display: flex; align-items: center; gap: .35rem;
  padding: .4rem .7rem;
  background: none;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text-muted);
  font-family: var(--font-body);
  font-size: .8rem;
  cursor: pointer;
  transition: all .2s;
}
.locale-btn:hover { border-color: var(--color-accent); color: var(--color-text); }
.locale-code { font-weight: 600; }
.locale-arrow { font-size: .65rem; transition: transform .2s; }
.locale-arrow.rotated { transform: rotate(180deg); }

.locale-dropdown {
  position: absolute;
  top: calc(100% + .4rem);
  right: 0;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  z-index: 200;
  min-width: 140px;
  overflow: hidden;
}
.locale-option {
  display: flex; align-items: center; gap: .6rem;
  width: 100%; padding: .6rem 1rem;
  background: none; border: none;
  color: var(--color-text-muted);
  font-family: var(--font-body);
  font-size: .875rem;
  cursor: pointer;
  transition: background .15s, color .15s;
  text-align: left;
}
.locale-option:hover { background: var(--color-surface-2); color: var(--color-text); }
.locale-option.active { color: var(--color-accent); font-weight: 600; background: var(--color-accent-soft); }

.dropdown-enter-active, .dropdown-leave-active { transition: all .2s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
