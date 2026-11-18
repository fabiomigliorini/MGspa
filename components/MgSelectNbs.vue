<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import MgSelect from '@components/MgSelect.vue'

// ===== Padrão LOCAL por parâmetro =====
// NBS (Nomenclatura Brasileira de Serviços) da NFS-e. A lista depende do código de
// tributação nacional: o backend devolve só os NBS da correlação oficial do item da
// LC 116 (ou todos, se o item não tiver correlação). Cacheia uma lista por código.
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  codtributacaonacional: { type: [Number, String], default: null },
  label: { type: String, default: 'NBS' },
  clearable: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select'])

const cache = useSelectCacheStore()
const ENDPOINT = 'v1/select/nbs'

const opcoes = ref([])
const carregando = ref(false)
// O valor atual quando ele não está na lista do código (nota antiga, correlação mudou)
const avulso = ref(null)

const chave = computed(() => `nbs-${props.codtributacaonacional || 'todos'}`)
const lista = computed(() => cache.entities[chave.value]?.items || [])
const todos = computed(() => {
  const itens = lista.value
  if (avulso.value && !itens.find((o) => o.value === avulso.value.value)) {
    return [avulso.value, ...itens]
  }
  return itens
})

async function garantirValorAtual() {
  avulso.value = null
  if (!props.modelValue || lista.value.find((o) => o.value === props.modelValue)) return
  try {
    avulso.value = await cache.loadById('nbs', ENDPOINT, props.modelValue)
  } catch {
    avulso.value = null
  }
}

async function carregar({ limparForaDaLista = false } = {}) {
  carregando.value = true
  try {
    const url = props.codtributacaonacional
      ? `${ENDPOINT}?codtributacaonacional=${props.codtributacaonacional}`
      : ENDPOINT
    await cache.loadList(chave.value, url)
    // Trocou o código e o NBS escolhido não vale para ele: limpa
    if (
      limparForaDaLista &&
      props.modelValue &&
      !lista.value.find((o) => o.value === props.modelValue)
    ) {
      emit('update:modelValue', null)
      emit('select', null)
      avulso.value = null
    } else {
      await garantirValorAtual()
    }
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

watch(
  () => props.codtributacaonacional,
  (novo, antigo) => {
    if (novo !== antigo) carregar({ limparForaDaLista: true })
  },
)

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
