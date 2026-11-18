<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import MgSelect from '@components/MgSelect.vue'

// ===== Padrão LOCAL =====
// Código de tributação nacional da NFS-e (lista nacional de serviços, ~340 códigos).
// Carrega TUDO de v1/select/tributacao-nacional, cacheia e filtra no front pelo código
// (com ou sem pontos) e pela descrição.
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Código de Tributação Nacional' },
  clearable: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select'])

const cache = useSelectCacheStore()
const ENTITY = 'tributacao-nacional'
const ENDPOINT = 'v1/select/tributacao-nacional'

const opcoes = ref([])
const carregando = ref(false)

const todos = computed(() => cache.entities[ENTITY]?.items || [])

async function carregar() {
  carregando.value = true
  try {
    await cache.loadList(ENTITY, ENDPOINT)
    opcoes.value = todos.value
  } catch {
    opcoes.value = []
  } finally {
    carregando.value = false
  }
}

function filtrar(val, update) {
  update(() => {
    const needle = (val || '').toLowerCase()
    const digitos = needle.replace(/\D/g, '')
    opcoes.value = needle
      ? todos.value.filter(
          (v) =>
            (v.label || '').toLowerCase().includes(needle) ||
            (digitos.length > 0 && String(v.codigo || '').startsWith(digitos)),
        )
      : todos.value
  })
}

function onUpdate(v) {
  emit('update:modelValue', v)
  emit('select', todos.value.find((o) => o.value === v) || null)
}

onMounted(() => carregar())
</script>

<template>
  <MgSelect
    :model-value="modelValue"
    :options="opcoes"
    :label="label"
    use-input
    fill-input
    hide-selected
    input-debounce="100"
    outlined
    :clearable="clearable"
    :loading="carregando"
    emit-value
    map-options
    @filter="filtrar"
    @update:model-value="onUpdate"
    v-bind="$attrs"
  >
    <template #no-option>
      <q-item><q-item-section class="text-grey-6">Nenhum registro</q-item-section></q-item>
    </template>
    <template #option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section>
          <q-item-label lines="2">{{ scope.opt.label }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template v-if="$slots.prepend" #prepend><slot name="prepend" /></template>
    <template v-if="$slots.before" #before><slot name="before" /></template>
    <template v-if="$slots.after" #after><slot name="after" /></template>
    <template v-if="$slots.hint" #hint><slot name="hint" /></template>
  </MgSelect>
</template>
