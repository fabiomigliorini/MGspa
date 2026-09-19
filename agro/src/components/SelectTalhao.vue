<script setup>
// Talhão (plantio) da carga — escolhido NO MAPA (cultura/safra → fazenda →
// polígono), não num dropdown. O campo é só leitura e mostra o rótulo; o
// clique abre o PlantioMapaDialog. Emite `update:modelValue` (codplantio) e
// `select` (o plantio inteiro — a carga precisa do codsafra dele).
import { ref, computed } from 'vue'
import { useCargaStore } from 'src/stores/carga'
import { corTalhao } from 'src/utils/coresTalhao'
import PlantioMapaDialog from 'components/PlantioMapaDialog.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { type: Number, default: null },
  codsafra: { type: Number, default: null },
  label: { type: String, default: 'Talhão' },
})
const emit = defineEmits(['update:modelValue', 'select'])

const store = useCargaStore()
const dialog = ref(false)

const plantio = computed(() => (props.modelValue ? store.plantioPorId(props.modelValue) : null))
const rotulo = computed(() => plantio.value?.rotulo || null)
const cor = computed(() => (plantio.value ? corTalhao(plantio.value) : null))

function onSelect(p) {
  emit('update:modelValue', p.codplantio)
  emit('select', p)
}
</script>

<template>
  <q-input
    :model-value="rotulo"
    :label="label"
    outlined
    readonly
    placeholder="Clique para escolher no mapa"
    class="cursor-pointer"
    v-bind="$attrs"
    @click="dialog = true"
  >
    <template #prepend>
      <q-icon v-if="cor" name="circle" :style="{ color: cor }" />
      <q-icon v-else name="grass" color="grey-5" />
    </template>
    <template #append>
      <q-btn flat round dense icon="map" color="primary" @click.stop="dialog = true">
        <q-tooltip>Escolher no mapa</q-tooltip>
      </q-btn>
    </template>
  </q-input>

  <PlantioMapaDialog
    v-model="dialog"
    :codplantio="modelValue"
    :codsafra="codsafra"
    @select="onSelect"
  />
</template>
