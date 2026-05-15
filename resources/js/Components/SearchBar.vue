<template>
  <div class="search-wrap">
    <div class="search-input-wrap">
      <SearchIcon class="search-icon" />
      <input
        v-model="query"
        type="text"
        placeholder="Rechercher une question..."
        class="search-input"
        @keydown.enter="submit"
        @focus="focused = true"
        @blur="onBlur"
      />
      <button v-if="query" class="clear-btn" @mousedown.prevent="clear">✕</button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import SearchIcon from '@/Components/Icons/SearchIcon.vue'

const query = ref(new URLSearchParams(window.location.search).get('q') || '')
const focused = ref(false)

let debounce = null

watch(query, (val) => {
  clearTimeout(debounce)
  debounce = setTimeout(() => {
    if (val.length > 1 || val === '') submit()
  }, 400)
})

const submit = () => {
  router.get(route('questions.index'), { q: query.value || undefined }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const clear = () => {
  query.value = ''
  submit()
}

const onBlur = () => {
  setTimeout(() => { focused.value = false }, 200)
}
</script>

<style scoped>
.search-wrap { position: relative; width: 100%; }
.search-input-wrap {
  display: flex;
  align-items: center;
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 0 .75rem;
  gap: .5rem;
  transition: border-color .2s, box-shadow .2s;
}
.search-input-wrap:focus-within {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}
.search-icon { width: 16px; height: 16px; color: var(--color-text-muted); flex-shrink: 0; }
.search-input {
  flex: 1;
  background: none;
  border: none;
  outline: none;
  color: var(--color-text);
  font-family: var(--font-body);
  font-size: .875rem;
  padding: .6rem 0;
}
.search-input::placeholder { color: var(--color-text-faint); }
.clear-btn {
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  font-size: .75rem;
  padding: .2rem;
  border-radius: 50%;
  line-height: 1;
  transition: color .2s;
}
.clear-btn:hover { color: var(--color-text); }
</style>
