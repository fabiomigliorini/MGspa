<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

// ===== Padrão LOCAL (entidade < 100 registros) =====
// Carrega TUDO uma vez de v1/select/estado, cacheia (lista + byId no store
// compartilhado), filtra no FRONT ao digitar e tem botão de REFRESH (append)
// pra invalidar o cache e recarregar. clearable é opcional (default false).
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Estado' },
  clearable: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const cache = useSelectCacheStore()
const ENTITY = 'estado'
const ENDPOINT = 'v1/select/estado'

const opcoes = ref([])
const carregando = ref(false)

const permitidos = computed(() => cache.entities[ENTITY]?.items || [])

async function carregar(force = false) {
  carregando.value = true
  try {
    await cache.loadList(ENTITY, ENDPOINT, { force })
    opcoes.value = permitidos.value
  } catch {
    opcoes.value = []
  } finally {
    carregando.value = false
  }
}

function atualizar() {
  cache.invalidate(ENTITY)
  carregar(true)
}

function filtrar(val, update) {
  update(() => {
    const needle = (val || '').toLowerCase()
    opcoes.value = needle
      ? permitidos.value.filter((v) => (v.label || '').toLowerCase().includes(needle))
      : permitidos.value
  })
}

onMounted(() => carregar())
</script>

<template>
  <q-select
    :model-value="modelValue"
    :options="opcoes"
    :label="label"
    use-input
    input-debounce="100"
    outlined
    :clearable="clearable"
    :loading="carregando"
    emit-value
    map-options
    @filter="filtrar"
    @update:model-value="(v) => emit('update:modelValue', v)"
    v-bind="$attrs"
  >
    <template #append>
      <q-btn
        flat
        round
        size="sm"
        color="grey-7"
        icon="refresh"
        tabindex="-1"
        @click.stop="atualizar"
      >
        <q-tooltip>Atualizar lista</q-tooltip>
      </q-btn>
    </template>
    <template #no-option>
      <q-item><q-item-section class="text-grey-6">Nenhum registro</q-item-section></q-item>
    </template>
  </q-select>
</template>
