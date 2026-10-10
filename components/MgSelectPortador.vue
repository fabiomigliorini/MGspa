<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { logo } from '@components/cobranca/logos.js'

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
  // Agrupa em "Desta filial" (os em espécie de codfilial) e "Mais opções" (Caixa Financeiro,
  // bancos, espécie de outras filiais): a lista de destino da transferência (decisão 22).
  agrupar: { type: Boolean, default: false },
  codfilial: { type: [Number, String], default: null },
  // codportador que não aparecem (o próprio portador, na transferência)
  excluir: { type: Array, default: null },
  // { codportador: motivo }: aparecem desabilitados, com o motivo (gaveta fechada)
  bloqueios: { type: Object, default: null },
  // só os portadores em que o usuário tem pelo menos este papel (D depositante, O operador, G
  // gestor): destino da transferência = D, origem = O (doc-4)
  papel: { type: String, default: null },
  clearable: { type: Boolean, default: false },
  inativos: { type: Boolean, default: false },
  // só os que recebem PIX (com chave), com o ícone do banco: o PIX padrão do PDV
  pix: { type: Boolean, default: false },
  // Modo multiplo: v-model e Array, onde [] = sem filtro (todos). Espelha o backend,
  // que so aplica o IN quando o array vem preenchido.
  multiple: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select'])

const NIVEL = { D: 1, O: 2, G: 3 }

const cache = useSelectCacheStore()
const ENTITY = 'portador'
const ENDPOINT = 'v1/select/portador'

const opcoes = ref([])
const carregando = ref(false)

// ícone do banco (no PIX); sem logo do banco, o do PIX
const logoBanco = (opt) => logo(`/bancos/${opt.codbanco}.svg`) ?? logo('/bancos/pix.svg')
const selecionado = computed(() =>
  props.pix && !props.multiple && props.modelValue
    ? (cache.entities[ENTITY]?.items || []).find(
        (v) => Number(v.value) === Number(props.modelValue),
      )
    : null,
)

const permitidos = computed(() => {
  let todos = cache.entities[ENTITY]?.items || []
  if (props.pix) {
    todos = todos.filter((v) => v.pixdict)
  }
  if (props.tipos) {
    todos = todos.filter((v) => props.tipos.includes(v.tipo))
  }
  if (props.semGaveta) {
    todos = todos.filter((v) => !v.gaveta)
  }
  if (props.papel) {
    const minimo = NIVEL[props.papel]
    todos = todos.filter((v) => (NIVEL[v.papel] ?? 0) >= minimo)
  }
  if (props.excluir?.length) {
    const fora = new Set(props.excluir.map((c) => Number(c)))
    todos = todos.filter((v) => !fora.has(Number(v.value)))
  }
  if (props.bloqueios) {
    todos = todos.map((v) =>
      props.bloqueios[v.value] ? { ...v, disable: true, motivo: props.bloqueios[v.value] } : v,
    )
  }
  if (!props.filiais) return todos
  const set = new Set(props.filiais.map((f) => Number(f)))
  return todos.filter((v) => set.has(Number(v.codfilial)))
})

// Com agrupar, intercala cabecalhos (opcoes desabilitadas) entre os grupos.
function agrupados(lista) {
  if (!props.agrupar) return lista
  const daFilial = (v) => v.tipo === 'E' && Number(v.codfilial) === Number(props.codfilial)
  const desta = lista.filter(daFilial)
  const mais = lista.filter((v) => !daFilial(v))
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
        <q-item-section v-if="pix" avatar>
          <q-avatar><q-img :src="logoBanco(scope.opt)" /></q-avatar>
        </q-item-section>
        <q-item-section>
          <q-item-label :class="scope.opt.inativo ? 'text-strike text-grey-6' : ''">
            {{ scope.opt.label }}
          </q-item-label>
          <q-item-label v-if="pix && scope.opt.banco" caption>{{ scope.opt.banco }}</q-item-label>
          <q-item-label v-if="scope.opt.motivo" caption>{{ scope.opt.motivo }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template v-if="$slots.prepend" #prepend><slot name="prepend" /></template>
    <template v-else-if="selecionado" #prepend>
      <q-avatar size="md"><q-img :src="logoBanco(selecionado)" /></q-avatar>
    </template>
    <template v-if="$slots.before" #before><slot name="before" /></template>
    <template v-if="$slots.after" #after><slot name="after" /></template>
    <template v-if="$slots.hint" #hint><slot name="hint" /></template>
  </q-select>
</template>
