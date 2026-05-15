<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="toast"
          :class="'toast-' + toast.type"
          @click="remove(toast.id)"
        >
          <span class="toast-icon">{{ icons[toast.type] }}</span>
          <span>{{ toast.message }}</span>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '@/Composables/useToast'
const { toasts, remove } = useToast()
const icons = { success: '✓', error: '✕', info: 'ℹ' }
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 1.5rem;
  right: 1.5rem;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: .5rem;
  pointer-events: none;
}
.toast {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: .75rem 1.25rem;
  font-size: .875rem;
  color: var(--color-text);
  box-shadow: var(--shadow-lg);
  display: flex;
  align-items: center;
  gap: .6rem;
  pointer-events: all;
  cursor: pointer;
  min-width: 240px;
  max-width: 360px;
}
.toast-success { border-color: rgba(34,197,94,.4); }
.toast-success .toast-icon { color: #22c55e; }
.toast-error { border-color: rgba(239,68,68,.4); }
.toast-error .toast-icon { color: #ef4444; }
.toast-info .toast-icon { color: var(--color-accent); }

.toast-enter-active, .toast-leave-active { transition: all .3s ease; }
.toast-enter-from { opacity: 0; transform: translateX(20px); }
.toast-leave-to { opacity: 0; transform: translateX(20px); }
</style>
