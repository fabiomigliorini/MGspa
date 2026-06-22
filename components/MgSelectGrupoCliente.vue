<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

// ===== Padrão LOCAL (entidade < 100 registros) =====
// Carrega TUDO uma vez de v1/select/grupo-cliente, cacheia, filtra no FRONT e
// tem REFRESH no append. clearable opcional (default false).
// Suporta `multiple`: nesse modo o v-model é Array|null, onde null = TODOS os
// grupos (canônico) e [] = nenhum — espelha o filtro do contas.
const props = defineProps({
  modelValue: { type: [Number, String, Array], default: null },
  label: { type: String, default: 'Grupo de Cliente' },
  clearable: { type: Boolean, default: false },
  inativos: { type: Boolean, default: false },
  multiple: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select'])

const cache = useSelectCacheStore()
const ENTITY = 'grupoCliente'
const ENDPOINT = 'v1/select/grupo-cliente'

const opcoes = ref([])
const carregando = ref(false)

const permitidos = computed(() => cache.entities[ENTITY]?.items || [])
const todosIds = computed(() => permitidos.value.map((v) => v.value))

// No modo multiple, null = todos -> manda a lista inteira pro q-select marcar tudo.
const valorQSelect = computed(() => {
  if (!props.multiple) return props.modelValue
  return props.modelValue === null ? todosIds.value : props.modelValue || []
})

const rotulo = computed(() => {
  if (!props.multiple) return undefined
  const m = props.modelValue
  if (Array.isArray(m) && m.length === 0) return 'Nenhum grupo'
  if (m === null) return 'Todos os grupos'
  return `${m.length} de ${todosIds.value.length} grupos`
})

async function carregar(force = false) {
  carregando.value = true
  try {
    await cache.loadList(ENTITY, ENDPOINT, { force, inativos: props.inativos })
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

function onUpdate(v) {
  if (!props.multiple) {
    emit('update:modelValue', v)
    emit('select', permitidos.value.find((o) => o.value === v) || null)
    return
  }
  const arr = Array.isArray(v) ? v : []
  // seleção completa volta a ser representada como null (canônico)
  emit('update:modelValue', arr.length > 0 && arr.length === todosIds.value.length ? null : arr)
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
    <template #append>
      <q-icon name="refresh" size="xs" class="cursor-pointer text-grey-7" @click.stop="atualizar"
        ><q-tooltip>Atualizar lista</q-tooltip></q-icon
      >
    </template>
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
