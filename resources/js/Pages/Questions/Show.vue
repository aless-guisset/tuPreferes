<template>
  <AppLayout>
    <div class="show-layout">
      <div class="show-main">
        <!-- Back -->
        <Link :href="route('questions.index')" class="back-link">← Retour</Link>

        <QuestionCard :question="question" :delay="0" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import QuestionCard from '@/Components/QuestionCard.vue'

defineProps({ question: Object })
</script>

<style scoped>
.show-layout { max-width: 640px; margin: 0 auto; }
.show-main { display: flex; flex-direction: column; gap: 1.25rem; }
.back-link {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  color: var(--color-text-muted);
  text-decoration: none;
  font-size: .875rem;
  transition: color .2s;
}
.back-link:hover { color: var(--color-accent); }
</style>
