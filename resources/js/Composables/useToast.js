import { ref } from 'vue'

const toasts = ref([])
let nextId = 0

export function useToast() {
    const add = (message, type = 'success', duration = 3000) => {
        const id = ++nextId
        toasts.value.push({ id, message, type })
        setTimeout(() => remove(id), duration)
    }

    const remove = (id) => {
        toasts.value = toasts.value.filter(t => t.id !== id)
    }

    return { toasts, add, remove }
}
