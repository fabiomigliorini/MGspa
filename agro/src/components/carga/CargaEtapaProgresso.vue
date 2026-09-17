<script setup>
// Barra de progresso das etapas de UMA carga (segmentos na ordem do sentido
// dela). Reutilizada no item da listagem (sem rótulos) e no resumo (com).
import { computed } from 'vue'
import { etapasDaCarga, indiceEtapa, ETAPA_META } from 'src/utils/carga'

const props = defineProps({
  carga: { type: Object, required: true },
  labels: { type: Boolean, default: false },
})

const etapas = computed(() => etapasDaCarga(props.carga))
const atual = computed(() => indiceEtapa(props.carga))

function cor(i) {
  return i <= atual.value ? ETAPA_META[props.carga.etapa]?.color || 'grey-5' : 'grey-3'
}
</script>

<template>
  <div class="row no-wrap q-gutter-x-xs">
    <div v-for="(e, i) in etapas" :key="e" class="col">
      <div class="etapa-segmento rounded-borders" :class="`bg-${cor(i)}`" />
      <div
        v-if="labels"
        class="text-caption text-center ellipsis q-mt-xs"
        :class="i <= atual ? 'text-grey-9' : 'text-grey-5'"
      >
        {{ ETAPA_META[e]?.curto || ETAPA_META[e]?.label }}
        <q-tooltip>{{ ETAPA_META[e]?.label }}</q-tooltip>
      </div>
    </div>
  </div>
</template>

<style scoped>
.etapa-segmento {
  height: 6px;
}
</style>
