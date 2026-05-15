import { ref, watch, onMounted } from 'vue'

const theme = ref('dark')

export function useTheme() {
    const toggle = () => {
        theme.value = theme.value === 'dark' ? 'light' : 'dark'
        localStorage.setItem('theme', theme.value)
        applyTheme(theme.value)
    }

    const applyTheme = (t) => {
        document.documentElement.classList.remove('dark', 'light')
        document.documentElement.classList.add(t)
    }

    onMounted(() => {
        const saved = localStorage.getItem('theme')
        const preferred = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'
        theme.value = saved || preferred
        applyTheme(theme.value)
    })

    return { theme, toggle }
}
