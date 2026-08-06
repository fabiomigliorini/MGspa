<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

// ===== Padrão LOCAL (entidade < 100 registros) =====
// Carrega TUDO uma vez de v1/select/rubrica, cacheia (lista + byId no store
// compartilhado) e filtra no FRONT ao digitar. clearable é opcional (default false).
//
// O endpoint devolve, além de value/label, os campos do catálogo usados como
// snapshot ao vincular a rubrica (tipovalor, tipocondicao, recorrente,
// valorpadrao, valorunitariopadrao). O evento `select` emite a opção INTEIRA,
// pra quem consome pré-preencher o formulário sem outra chamada.
//
// permiteAvulsa prepende a pseudo-opção "Outros (avulsa)" (value 0): rubrica
// digitada à mão, que grava codrubrica null e só tem descrição.
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Rubrica' },
  clearable: { type: Boolean, default: false },
  inativos: { type: Boolean, default: false },
  permiteAvulsa: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select'])

const cache = useSelectCacheStore()
const ENTITY = 'rubrica'
const ENDPOINT = 'v1/select/rubrica'

const OPCAO_AVULSA = { value: 0, label: 'Outros (avulsa)' }

const filtradas = ref([])
const carregando = ref(false)

// O store cacheia inativos num bucket separado (`{ent}__inativos`) — ler sempre
// `entities[ENTITY]` devolveria lista vazia quando a prop inativos esta ligada.
const chaveCache = computed(() => (props.inativos ? `${ENTITY}__inativos` : ENTITY))
const permitidos = computed(() => cache.entities[chaveCache.value]?.items || [])

// A avulsa fica sempre no topo, fora do filtro: é uma escolha, não um registro.
const opcoes = computed(() =>
  props.permiteAvulsa ? [OPCAO_AVULSA, ...filtradas.value] : filtradas.value,
)

async function carregar() {
  carregando.value = true
  try {
    await cache.loadList(ENTITY, ENDPOINT, { inativos: props.inativos })
    filtradas.value = permitidos.value
  } catch {
    filtradas.value = []
  } finally {
    carregando.value = false
  }
}

function filtrar(val, update) {
  update(() => {
    const needle = (val || '').toLowerCase()
    filtradas.value = needle
      ? permitidos.value.filter((v) => (v.label || '').toLowerCase().includes(needle))
      : permitidos.value
  })
}

function onUpdate(v) {
  emit('update:modelValue', v)
  // Avulsa (0) e vazio não têm registro de catálogo: emite null.
  emit('select', v ? permitidos.value.find((o) => o.value === v) || null : null)
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
      <q-item><q-item-section class="text-grey-6">Nenhum registro</q-item-section></q-item>
    </template>
    <template #option="scope">
      <q-item v-bind="scope.itemProps">
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
