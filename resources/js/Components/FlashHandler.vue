<template></template>

<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'

const page = usePage()
const { add } = useToast()

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) add(flash.success, 'success')
    if (flash?.error)   add(flash.error,   'error')
    if (flash?.info)    add(flash.info,     'info')
  },
  { immediate: true, deep: true }
)
</script>
