<script setup>
import { ref, onMounted } from 'vue'
import { api } from 'src/services/api'

const value = defineModel({ type: String, default: null })

const impressoras = ref([])
const carregando = ref(false)

onMounted(async () => {
  carregando.value = true
  try {
    const { data } = await api.get('v1/select/impressora')
    impressoras.value = Array.isArray(data) ? data : data.data || []
  } finally {
    carregando.value = false
  }
})
</script>

<template>
  <q-select
    v-model="value"
    :options="impressoras"
    label="Impressora"
    outlined
    emit-value
    map-options
    :loading="carregando"
    :bottom-slots="false"
  >
    <template #prepend><q-icon name="print" /></template>
  </q-select>
</template>
