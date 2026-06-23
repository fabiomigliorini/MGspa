<script setup>
import { storeToRefs } from 'pinia'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import MgEmptyState from '@components/MgEmptyState.vue'

// Card "Entregas". Especialista no físico entregue: lista os movimentos deste
// contrato no extrato de grãos (cada carga que o moveu, mais ajustes manuais).
// Somente leitura — as cargas são lançadas no pátio. Lê do store da tela.
const store = useContratoDetalheStore()
const { entregas, carregadokg, carregadosc } = storeToRefs(store)

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}
</script>

<template>
  <q-card flat bordered class="q-mb-md">
    <q-item class="bg-primary text-white">
      <q-item-section>
        <q-item-label class="text-subtitle1">Entregas</q-item-label>
        <q-item-label class="text-caption"
          >{{ fmt(carregadokg) }} kg (≈ {{ fmt(carregadosc, 1) }} sc) entregue</q-item-label
        >
      </q-item-section>
    </q-item>
    <q-separator />
    <q-list separator>
      <q-item v-for="e in entregas" :key="e.codmovimentograo">
        <q-item-section avatar>
          <q-avatar
            :color="e.manual ? 'deep-purple-5' : 'green-6'"
            text-color="white"
            :icon="e.manual ? 'edit_note' : 'local_shipping'"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label
            >{{ fmt(e.quantidadekg) }} kg
            <span class="text-caption text-grey-6">(≈ {{ fmt(e.quantidadesc, 1) }} sc)</span>
          </q-item-label>
          <q-item-label caption>
            {{ e.Carga?.placa || (e.manual ? 'Ajuste manual' : '—') }}
            <span v-if="e.data"> · {{ new Date(e.data).toLocaleDateString('pt-BR') }}</span>
          </q-item-label>
        </q-item-section>
      </q-item>
      <MgEmptyState v-if="!entregas.length" plain icon="local_shipping">
        Nenhuma entrega ainda. Carregue no pátio.
      </MgEmptyState>
    </q-list>
  </q-card>
</template>
