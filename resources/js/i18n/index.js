import fr from './fr.json'
import en from './en.json'
import nl from './nl.json'
import es from './es.json'
import de from './de.json'

export const locales = { fr, en, nl, es, de }

export const availableLocales = [
  { code: 'fr', label: 'Français', flag: '🇫🇷' },
  { code: 'en', label: 'English',  flag: '🇬🇧' },
  { code: 'nl', label: 'Nederlands', flag: '🇳🇱' },
  { code: 'es', label: 'Español',  flag: '🇪🇸' },
  { code: 'de', label: 'Deutsch',  flag: '🇩🇪' },
]

export default locales
