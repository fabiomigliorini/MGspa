<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

// ===== Padrão LOCAL (entidade < 100 registros) =====
// Carrega TUDO de v1/select/moeda (mesma fonte do cadastro /moeda), cacheia
// (lista + byId no store compartilhado) e filtra no FRONT ao digitar. A lista é
// RECARREGADA a cada abertura do dropdown (@popup-show), então uma moeda nova
// cadastrada aparece sem precisar de botão de refresh.
// value = iso (BRL/USD/…), que é o que tblcontratofixacao.moeda guarda.
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Moeda' },
  clearable: { type: Boolean, default: false },
  inativos: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select'])

const cache = useSelectCacheStore()
const ENTITY = 'moeda'
const ENDPOINT = 'v1/select/moeda'

const opcoes = ref([])
const carregando = ref(false)

const permitidos = computed(() => cache.entities[ENTITY]?.items || [])

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
      ? permitidos.value.filter((m) =>
          [m.moeda, m.sigla, m.iso].some((c) => (c || '').toLowerCase().includes(needle)),
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
    @popup-show="atualizar"
    @update:model-value="onUpdate"
    v-bind="$attrs"
  >
    <template #no-option>
      <q-item><q-item-section class="text-grey-6">Nenhuma moeda</q-item-section></q-item>
    </template>
    <template #option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <span :class="scope.opt.inativo ? 'text-strike text-grey-6' : 'text-weight-medium'">
            {{ scope.opt.sigla }}
          </span>
        </q-item-section>
        <q-item-section>
          <q-item-label :class="scope.opt.inativo ? 'text-strike text-grey-6' : ''">
            {{ scope.opt.moeda }}
          </q-item-label>
          <q-item-label caption>{{ scope.opt.iso }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template v-if="$slots.prepend" #prepend><slot name="prepend" /></template>
    <template v-if="$slots.before" #before><slot name="before" /></template>
    <template v-if="$slots.after" #after><slot name="after" /></template>
    <template v-if="$slots.hint" #hint><slot name="hint" /></template>
  </q-select>
</template>
