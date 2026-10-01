<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

// ===== Padrão LOCAL (ativas < 100) =====
// Carrega TUDO uma vez de v1/select/maquineta, cacheia (lista + byId no store compartilhado)
// e filtra no FRONT ao digitar. clearable é opcional (default false).
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Maquineta' },
  // restringe às que aparecem no PDV da filial (dela + compartilhadas)
  codfilial: { type: [Number, String], default: null },
  // restringe à adquirente
  codpessoa: { type: [Number, String], default: null },
  // esconde uma maquineta (ex.: a própria, no "juntar com")
  excluir: { type: [Number, String], default: null },
  clearable: { type: Boolean, default: false },
  inativos: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select'])

const cache = useSelectCacheStore()
const ENTITY = 'maquineta'
const ENDPOINT = 'v1/select/maquineta'

const opcoes = ref([])
const carregando = ref(false)

const permitidos = computed(() => {
  let todas = cache.entities[ENTITY]?.items || []
  if (props.codfilial) {
    todas = todas.filter((m) => m.compartilhada || Number(m.codfilial) === Number(props.codfilial))
  }
  if (props.codpessoa) {
    todas = todas.filter((m) => Number(m.codpessoa) === Number(props.codpessoa))
  }
  if (props.excluir) {
    todas = todas.filter((m) => Number(m.codmaquineta) !== Number(props.excluir))
  }
  return todas
})

async function carregar() {
  carregando.value = true
  try {
    await cache.loadList(ENTITY, ENDPOINT, { inativos: props.inativos })
    opcoes.value = permitidos.value
  } catch {
    opcoes.value = []
  } finally {
    carregando.value = false
  }
}

function filtrar(val, update) {
  update(() => {
    const needle = (val || '').toLowerCase()
    opcoes.value = needle
      ? permitidos.value.filter((m) =>
          [m.label, m.serial, m.filial, m.adquirente].some((c) =>
            (c || '').toLowerCase().includes(needle),
          ),
        )
      : permitidos.value
  })
}

function onUpdate(v) {
  emit('update:modelValue', v)
  emit('select', (opcoes.value || []).find((o) => o.value === v) || null)
}

onMounted(() => carregar())
</script>

<template>
  <q-select
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
      <q-item><q-item-section class="text-grey-6">Nenhuma maquineta</q-item-section></q-item>
    </template>
    <template #option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section>
          <q-item-label :class="scope.opt.inativo ? 'text-strike text-grey-6' : ''">
            {{ scope.opt.label }}
          </q-item-label>
          <q-item-label caption>
            {{ scope.opt.adquirente }} ·
            {{ scope.opt.compartilhada ? 'Todas as filiais' : scope.opt.filial }}
            <template v-if="scope.opt.serial"> · {{ scope.opt.serial }}</template>
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template v-if="$slots.prepend" #prepend><slot name="prepend" /></template>
    <template v-if="$slots.before" #before><slot name="before" /></template>
    <template v-if="$slots.after" #after><slot name="after" /></template>
    <template v-if="$slots.hint" #hint><slot name="hint" /></template>
  </q-select>
</template>
