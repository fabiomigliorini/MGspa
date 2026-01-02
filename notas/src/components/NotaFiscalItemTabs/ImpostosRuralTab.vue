<script setup>
import { computed } from 'vue'
import { useNotaFiscalStore } from 'src/stores/notaFiscalStore'
import { storeToRefs } from 'pinia'

const notaFiscalStore = useNotaFiscalStore()

// State
const nota = computed(() => notaFiscalStore.currentNota)
const notaBloqueada = computed(() => {
  if (!nota.value) return false
  return ['AUT', 'CAN', 'INU'].includes(nota.value.status)
})

// Usa o editingItem do store diretamente (ref reativa)
const { editingItem } = storeToRefs(notaFiscalStore)

</script>

<template>

  <div class="text-subtitle1 text-weight-bold q-mb-md">
    <q-icon name="verified" size="sm" class="q-mr-xs" />
    CERTIFICAÇÃO
  </div>

  <div class="row q-col-gutter-md">
    <div class="col-12">
      <q-toggle v-model="editingItem.certidaosefazmt" label="Destacar Certidão SEFAZ/MT" color="primary"
        :disable="notaBloqueada" />
      <div class="text-caption text-grey-7 q-mt-xs">
        Destacar certidão de regularidade da SEFAZ Mato Grosso. Exige que as certidões estejam cadastradas tanto na
        pessoa do Emitente quando do Destinatário.
      </div>
    </div>
  </div>


  <!-- FETHAB -->
  <div class="text-subtitle1 text-weight-bold q-my-md">
    <q-icon name="nature" size="sm" class="q-mr-xs" />
    FETHAB (Fundo Estadual de Transporte e Habitação)
  </div>

  <div class="row q-col-gutter-md">
    <div class="col-6 col-sm-3">
      <q-input v-model.number="editingItem.fethabkg" label="Por Kg" outlined type="number" step="0.000001" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <div class="col-6 col-sm-3">
      <q-input v-model.number="editingItem.fethabvalor" label="Valor FETHAB" outlined type="number" step="0.01" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>
  </div>

  <!-- IAGRO -->
  <div class="text-subtitle1 text-weight-bold q-my-md">
    <q-icon name="eco" size="sm" class="q-mr-xs" />
    IAGRO (Instituto de Defesa Agropecuária)
  </div>

  <div class="row q-col-gutter-md">
    <div class="col-6 col-sm-3">
      <q-input v-model.number="editingItem.iagrokg" label="Por Kg" outlined type="number" step="0.000001" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <div class="col-6 col-sm-3">
      <q-input v-model.number="editingItem.iagrovalor" label="Valor IAGRO" outlined type="number" step="0.01" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>
  </div>

  <!-- FUNRURAL -->
  <div class="text-subtitle1 text-weight-bold q-my-md">
    <q-icon name="agriculture" size="sm" class="q-mr-xs" />
    FUNRURAL (Fundo de Assistência ao Trabalhador Rural)
  </div>

  <div class="row q-col-gutter-md">
    <div class="col-6 col-sm-3">
      <q-input v-model.number="editingItem.funruralpercentual" label="Alíquota" outlined type="number" step="0.01"
        min="0" max="100" suffix="%" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <div class="col-6 col-sm-3">
      <q-input v-model.number="editingItem.funruralvalor" label="Valor FUNRURAL" outlined type="number" step="0.01"
        min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>
  </div>

  <!-- SENAR -->
  <div class="text-subtitle1 text-weight-bold q-my-md">
    <q-icon name="school" size="sm" class="q-mr-xs" />
    SENAR (Serviço Nacional de Aprendizagem Rural)
  </div>

  <div class="row q-col-gutter-md">
    <div class="col-6 col-sm-3">
      <q-input v-model.number="editingItem.senarpercentual" label="Alíquota" outlined type="number" step="0.01" min="0"
        max="100" suffix="%" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <div class="col-6 col-sm-3">
      <q-input v-model.number="editingItem.senarvalor" label="Valor SENAR" outlined type="number" step="0.01" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>
  </div>
</template>
