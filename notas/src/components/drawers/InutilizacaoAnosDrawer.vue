<script setup>
import { storeToRefs } from 'pinia'
import { formataNumero } from '@components/formatters'
import { useInutilizacaoStore } from '../../stores/inutilizacaoStore'

const inutilizacaoStore = useInutilizacaoStore()
const { ano, anos } = storeToRefs(inutilizacaoStore)
</script>

<template>
  <div class="column full-height">
    <div class="q-pa-md bg-primary text-white">
      <div class="text-h6">
        <q-icon name="event" class="q-mr-sm" />
        Anos
      </div>
      <div class="text-caption">Histórico da filial selecionada</div>
    </div>

    <q-separator />

    <q-list>
      <q-item
        v-for="a in anos"
        :key="a.ano"
        clickable
        v-ripple
        :active="a.ano === ano"
        active-class="bg-blue-1 text-primary text-weight-medium"
        @click="inutilizacaoStore.selecionarAno(a.ano)"
      >
        <q-item-section>
          <q-item-label>{{ a.ano }}</q-item-label>
          <q-item-label caption>
            {{ formataNumero(a.faixas, 0) }} {{ a.faixas === 1 ? 'faixa' : 'faixas' }} —
            {{ formataNumero(a.numeros, 0) }} {{ a.numeros === 1 ? 'número' : 'números' }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </div>
</template>
