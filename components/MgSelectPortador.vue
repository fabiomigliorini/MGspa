<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

// ===== REFERÊNCIA do padrão LOCAL (entidade < 100 registros) =====
// Carrega TUDO uma vez de v1/select/portador, cacheia (lista + byId no store
// compartilhado) e filtra no FRONT ao digitar. clearable é opcional (default false).
const props = defineProps({
  modelValue: { type: [Number, String, Array], default: null },
  label: { type: String, default: 'Portador' },
  // Se array de codfilial, restringe aos portadores dessas filiais.
  filiais: { type: Array, default: null },
  // Se array de tipos (E, B, A, C, O), restringe a esses tipos.
  tipos: { type: Array, default: null },
  // Esconde as gavetas (espécie com PDV apontando): no contas não se baixa título em gaveta.
  semGaveta: { type: Boolean, default: false },
  // Agrupa em "Desta filial" (portadores de codfilial) e "Mais opções" (o resto).
  agrupar: { type: Boolean, default: false },
  codfilial: { type: [Number, String], default: null },
  clearable: { type: Boolean, default: false },
  inativos: { type: Boolean, default: false },
  // Modo multiplo: v-model e Array, onde [] = sem filtro (todos). Espelha o backend,
  // que so aplica o IN quando o array vem preenchido.
  multiple: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select'])

const cache = useSelectCacheStore()
const ENTITY = 'portador'
const ENDPOINT = 'v1/select/portador'

const opcoes = ref([])
const carregando = ref(false)

const permitidos = computed(() => {
  let todos = cache.entities[ENTITY]?.items || []
  if (props.tipos) {
    todos = todos.filter((v) => props.tipos.includes(v.tipo))
  }
  if (props.semGaveta) {
    todos = todos.filter((v) => !v.gaveta)
  }
  if (!props.filiais) return todos
  const set = new Set(props.filiais.map((f) => Number(f)))
  return todos.filter((v) => set.has(Number(v.codfilial)))
})

// Com agrupar, intercala cabecalhos (opcoes desabilitadas) entre os grupos.
function agrupados(lista) {
  if (!props.agrupar) return lista
  const desta = lista.filter((v) => Number(v.codfilial) === Number(props.codfilial))
  const mais = lista.filter((v) => Number(v.codfilial) !== Number(props.codfilial))
  const ret = []
  if (desta.length) ret.push({ header: true, label: 'Desta filial', disable: true }, ...desta)
  if (mais.length) ret.push({ header: true, label: 'Mais opções', disable: true }, ...mais)
  return ret
}

async function carregar() {
  carregando.value = true
  try {
    await cache.loadList(ENTITY, ENDPOINT, { inativos: props.inativos })
    opcoes.value = agrupados(permitidos.value)
  } catch {
    opcoes.value = []
  } finally {
    carregando.value = false
  }
}

function filtrar(val, update) {
  update(() => {
    const needle = (val || '').toLowerCase()
    opcoes.value = agrupados(
      needle
        ? permitidos.value.filter((v) => (v.label || '').toLowerCase().includes(needle))
        : permitidos.value,
    )
  })
}

function onUpdate(v) {
  if (props.multiple) {
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
    :model-value="modelValue"
    :options="opcoes"
    :label="label"
    :multiple="multiple"
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
      <q-item><q-item-section class="text-grey-6">Nenhum portador</q-item-section></q-item>
    </template>
    <template #option="scope">
      <q-item-label v-if="scope.opt.header" header>{{ scope.opt.label }}</q-item-label>
      <q-item
        v-else
        v-bind="scope.itemProps"
        :class="multiple && scope.selected ? 'bg-blue-1' : ''"
      >
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
