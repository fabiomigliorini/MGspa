<script setup>
import { computed } from 'vue'
import { formataNumero, formataData } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'

// Card "Cargas deste plantio". Lista o que formou o colhido do talhão: os
// movimentos dele no extrato de grãos (cada romaneio, mais ajustes manuais),
// com a PARTE deste talhão — romaneio dividido entre talhões vem rateado. É a
// mesma fonte do KPI Colhido, então a soma da lista bate com o número do topo.
// Somente leitura; o romaneio abre na ficha (/cargas/:codcarga).
const props = defineProps({
  movimentos: { type: Array, default: () => [] },
  // kg por saca da cultura da safra (60 quando vazio), o mesmo do KPI Colhido.
  pesosaca: { type: Number, default: 60 },
})

const totalKg = computed(() =>
  props.movimentos.reduce((soma, m) => soma + (Number(m.quantidadekg) || 0), 0),
)

// Mesma conta do KPI (SafraService): soma os kg e só então divide pela saca.
const totalSc = computed(() => totalKg.value / (props.pesosaca || 60))

const linkCarga = (m) =>
  m.codcarga ? { name: 'carga-detalhe', params: { codcarga: m.codcarga } } : undefined
</script>

<template>
  <q-card bordered flat class="q-mb-md">
    <q-item>
      <q-item-section avatar>
        <q-avatar color="blue-grey-1" text-color="blue-grey-8" icon="local_shipping" />
      </q-item-section>
      <q-item-section>
        <q-item-label class="text-subtitle1">Cargas deste plantio</q-item-label>
        <q-item-label caption>
          {{ formataNumero(totalKg, 0) }} kg (≈ {{ formataNumero(totalSc, 0) }} sc) colhido
        </q-item-label>
      </q-item-section>
    </q-item>
    <q-separator />
    <q-list separator>
      <q-item
        v-for="m in movimentos"
        :key="m.codmovimentograo"
        :to="linkCarga(m)"
        :clickable="!!m.codcarga"
      >
        <q-item-section avatar>
          <q-avatar
            :color="m.manual ? 'deep-purple-5' : 'green-6'"
            text-color="white"
            :icon="m.manual ? 'edit_note' : 'local_shipping'"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label>
            {{ formataNumero(m.quantidadekg, 0) }} kg
            <span class="text-caption text-grey-6"
              >(≈ {{ formataNumero(m.quantidadesc, 1) }} sc)</span
            >
          </q-item-label>
          <q-item-label v-if="m.manual" caption>
            Ajuste manual
            <span v-if="m.data"> · {{ formataData(m.data) }}</span>
            <span v-if="m.observacao"> · {{ m.observacao }}</span>
          </q-item-label>
          <template v-else>
            <q-item-label caption>
              {{ formataData(m.data) }}
              <span v-if="m.placa"> · {{ m.placa }}</span>
              <span v-if="m.motorista"> · {{ m.motorista }}</span>
            </q-item-label>
            <q-item-label v-if="m.destino" caption>→ {{ m.destino }}</q-item-label>
          </template>
        </q-item-section>
        <q-item-section v-if="m.codcarga" side>
          <q-icon name="chevron_right" color="grey-5" />
        </q-item-section>
      </q-item>
      <MgEmptyState v-if="!movimentos.length" plain icon="local_shipping">
        Nenhuma carga colhida deste talhão ainda.
      </MgEmptyState>
    </q-list>
  </q-card>
</template>
