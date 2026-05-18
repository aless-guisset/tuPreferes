import { ref, computed } from 'vue'
import { locales, availableLocales } from '@/i18n/index.js'

const currentLocale = ref(localStorage.getItem('locale') || navigator.language.slice(0, 2) || 'fr')

// Fallback sur fr si la langue n'est pas supportée
if (!locales[currentLocale.value]) {
  currentLocale.value = 'fr'
}

export function useI18n() {
  const setLocale = (code) => {
    if (!locales[code]) return
    currentLocale.value = code
    localStorage.setItem('locale', code)
  }

  const t = (key) => {
    const keys = key.split('.')
    let value = locales[currentLocale.value]
    for (const k of keys) {
      if (value === undefined) return key
      value = value[k]
    }
    return value ?? key
  }

  // Pluralisation simple : "1 vote | 2 votes" avec {n}
  const tp = (key, n) => {
    const str = t(key)
    if (!str.includes('|')) return str.replace('{n}', n)
    const parts = str.split('|').map(s => s.trim())
    const part  = n <= 1 ? parts[0] : parts[1]
    return part.replace('{n}', n)
  }

  return {
    t,
    tp,
    locale: currentLocale,
    setLocale,
    availableLocales,
  }
}
