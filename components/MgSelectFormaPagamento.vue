<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

// ===== Padrão LOCAL (entidade < 100 registros) =====
// Carrega TUDO uma vez de v1/select/forma-pagamento, cacheia (lista + byId no store
// compartilhado) e filtra no FRONT ao digitar. clearable é opcional (default false).
// Modo multiplo: v-model e Array, onde [] = sem filtro (todas). Espelha o backend,
// que so aplica o IN quando o array vem preenchido.
const props = defineProps({
  modelValue: { type: [Number, String, Array], default: null },
  label: { type: String, default: 'Forma de Pagamento' },
  clearable: { type: Boolean, default: false },
  inativos: { type: Boolean, default: false },
  multiple: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select'])

const cache = useSelectCacheStore()
const ENTITY = 'formaPagamento'
const ENDPOINT = 'v1/select/forma-pagamento'

const opcoes = ref([])
const carregando = ref(false)

const permitidos = computed(() => cache.entities[ENTITY]?.items || [])

// No modo multiple o q-select exige array; coage escalar legado (v-model antigo
// persistido) em [escalar] pra tela nao quebrar.
const selecionados = computed(() => {
  const m = props.modelValue
  if (Array.isArray(m)) return m
  return m === null || m === undefined || m === '' ? [] : [m]
})

const valorQSelect = computed(() => (props.multiple ? selecionados.value : props.modelValue))

const rotulo = computed(() => {
  if (!props.multiple) return undefined
  const sel = selecionados.value
  if (sel.length === 0) return undefined
  if (sel.length === 1) return permitidos.value.find((o) => o.value === sel[0])?.label || '1 forma'
  return `${sel.length} formas selecionadas`
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
      ? permitidos.value.filter((v) => (v.label || '').toLowerCase().includes(needle))
      : permitidos.value
  })
}

function onUpdate(v) {
  if (props.multiple) {
    // clearable manda null; normaliza pra [] (= sem filtro)
    emit('update:modelValue', Array.isArray(v) ? v : [])
    return
  }
  emit('update:modelValue', v)
  emit('select', (opcoes.value || []).find((o) => o.value === v) || null)
}

onMounted(() => carregar())
</script>

<template>
  <q-select
    :model-value="valorQSelect"
    :options="opcoes"
    :label="label"
    :multiple="multiple"
    :display-value="rotulo"
    use-input
    :fill-input="!multiple"
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
      <q-item v-bind="scope.itemProps" :class="multiple && scope.selected ? 'bg-blue-1' : ''">
        <q-item-section>
          <q-item-label :class="scope.opt.inativo ? 'text-strike text-grey-6' : ''">
            {{ scope.opt.label }}
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
