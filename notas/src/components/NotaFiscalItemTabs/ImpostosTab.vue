<script setup>
import { computed } from 'vue'
import { useNotaFiscalStore } from 'src/stores/notaFiscalStore'
import { ICMS_CST_OPTIONS, CSOSN_OPTIONS, IPI_CST_OPTIONS, PIS_CST_OPTIONS, COFINS_CST_OPTIONS } from 'src/constants/notaFiscal'
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

  <!-- ICMS -->
  <q-card flat bordered class="q-mb-md full-height">
    <q-card-section class="bg-primary text-white">
      <q-icon name="account_balance" size="sm" class="q-mr-xs" />
      ICMS
    </q-card-section>
    <q-card-section>
      <div class="row q-col-gutter-md">

        <!-- CFOP -->
        <div class="col-3 col-sm-2">
          <q-input v-model.number="editingItem.codcfop" label="CFOP *" outlined type="number" :disable="notaBloqueada"
            input-class="text-right" />
        </div>

        <!-- CST / CSOSN -->
        <div class="col-9 col-sm-10" v-if="!editingItem.csosn">
          <q-select v-model="editingItem.icmscst" :options="ICMS_CST_OPTIONS" label="CST" outlined emit-value
            map-options clearable :disable="notaBloqueada" />
        </div>

        <div class="col-9 col-sm-10" v-else>
          <q-select v-model="editingItem.csosn" :options="CSOSN_OPTIONS" label="CSOSN " hint="Simples Nacional" outlined
            emit-value map-options clearable :disable="notaBloqueada" />
        </div>

        <!-- Base de Cálculo -->
        <div class="col-6 col-sm-3">
          <q-input v-model.number="editingItem.icmsbasepercentual" label="% da Base" outlined type="number" step="0.01"
            min="0" max="100" suffix="%" :disable="notaBloqueada" input-class="text-right" />
        </div>

        <div class="col-6 col-sm-3">
          <q-input v-model.number="editingItem.icmsbase" label="Base de Cálculo" outlined type="number" step="0.01"
            min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right" />
        </div>

        <!-- Alíquota -->
        <div class="col-6 col-sm-3">
          <q-input v-model.number="editingItem.icmspercentual" label="Alíquota" outlined type="number" step="0.01"
            min="0" max="100" suffix="%" :disable="notaBloqueada" input-class="text-right" />
        </div>

        <!-- Valor ICMS -->
        <div class="col-6 col-sm-3">
          <q-input v-model.number="editingItem.icmsvalor" label="Valor ICMS" outlined type="number" step="0.01" min="0"
            prefix="R$" :disable="notaBloqueada" input-class="text-right" />
        </div>

      </div>
    </q-card-section>
  </q-card>
  <div class="text-subtitle1 text-weight-bold q-mb-md bg-primary text-white q-pa-sm">
    <q-icon name="" size="sm" class="q-mr-xs" />
  </div>




  <!-- ICMS ST -->
  <div class="text-subtitle1 text-weight-bold q-my-md bg-primary text-white q-pa-sm">
    <q-icon name="account_balance" size="sm" class="q-mr-xs" />
    ICMS ST (Substituição Tributária)
  </div>

  <div class="row q-col-gutter-md">

    <div class="col-4">
      <q-input v-model.number="editingItem.icmsstbase" label="Base " outlined type="number" step="0.01" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <div class="col-4">
      <q-input v-model.number="editingItem.icmsstpercentual" label="Alíquota " outlined type="number" step="0.01"
        min="0" max="100" suffix="%" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <div class="col-4">
      <q-input v-model.number="editingItem.icmsstvalor" label="Valor ST" outlined type="number" step="0.01" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>
  </div>

  <!-- IPI -->
  <div class="text-subtitle1 text-weight-bold q-my-md bg-primary text-white q-pa-sm">
    <q-icon name="inventory" size="sm" class="q-mr-xs" />
    IPI
  </div>

  <div class="row q-col-gutter-md">

    <!-- CST -->
    <div class="col-12 ">
      <q-select v-model="editingItem.ipicst" :options="IPI_CST_OPTIONS" label="IPI CST" outlined emit-value map-options
        clearable :disable="notaBloqueada" />
    </div>

    <!-- Base de Cálculo -->
    <div class="col-4">
      <q-input v-model.number="editingItem.ipibase" label="Base " outlined type="number" step="0.01" min="0" prefix="R$"
        :disable="notaBloqueada" input-class="text-right" />
    </div>

    <!-- Alíquota -->
    <div class="col-4">
      <q-input v-model.number="editingItem.ipipercentual" label="Alíquota" outlined type="number" step="0.01" min="0"
        max="100" suffix="%" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <!-- Valor IPI -->
    <div class="col-4">
      <q-input v-model.number="editingItem.ipivalor" label="Valor IPI" outlined type="number" step="0.01" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <!-- Valor Devolução -->
    <div class="col-6">
      <q-input v-model.number="editingItem.ipidevolucaovalor" label="IPI Devolvido " outlined type="number" step="0.01"
        min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <!-- Devolução Percentual -->
    <div class="col-6">
      <q-input v-model.number="editingItem.devolucaopercentual" label="% Devolução" outlined type="number" step="0.01"
        min="0" max="100" suffix="%" :disable="notaBloqueada" input-class="text-right" />
    </div>

  </div>

  <!-- PIS -->
  <div class="text-subtitle1 text-weight-bold q-my-md bg-primary text-white q-pa-sm">
    <q-icon name="money" size="sm" class="q-mr-xs" />
    PIS
  </div>

  <div class="row q-col-gutter-md">
    <!-- CST -->
    <div class="col-12">
      <q-select v-model="editingItem.piscst" :options="PIS_CST_OPTIONS" label="PIS CST" outlined emit-value map-options
        clearable :disable="notaBloqueada" />
    </div>

    <!-- Base -->
    <div class="col-4">
      <q-input v-model.number="editingItem.pisbase" label="Base" outlined type="number" step="0.01" min="0" prefix="R$"
        :disable="notaBloqueada" input-class="text-right" />
    </div>

    <!-- Alíquota -->
    <div class="col-4">
      <q-input v-model.number="editingItem.pispercentual" label="Alíquota" outlined type="number" step="0.01" min="0"
        max="100" suffix="%" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <!-- Valor -->
    <div class="col-4">
      <q-input v-model.number="editingItem.pisvalor" label="Valor PIS" outlined type="number" step="0.01" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>
  </div>

  <!-- COFINS -->
  <div class="text-subtitle1 text-weight-bold q-my-md bg-primary text-white q-pa-sm">
    <q-icon name="attach_money" size="sm" class="q-mr-xs" />
    COFINS
  </div>

  <div class="row q-col-gutter-md">

    <!-- CST -->
    <div class="col-12">
      <q-select v-model="editingItem.cofinscst" :options="COFINS_CST_OPTIONS" label="COFINS CST" outlined emit-value
        map-options clearable :disable="notaBloqueada" />
    </div>

    <!-- Base -->
    <div class="col-4">
      <q-input v-model.number="editingItem.cofinsbase" label="Base de Cálculo" outlined type="number" step="0.01"
        min="0" prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <!-- Alíquota -->
    <div class="col-4">
      <q-input v-model.number="editingItem.cofinspercentual" label="Alíquota COFINS" outlined type="number" step="0.01"
        min="0" max="100" suffix="%" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <!-- Valor -->
    <div class="col-4">
      <q-input v-model.number="editingItem.cofinsvalor" label="Valor COFINS" outlined type="number" step="0.01" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>
  </div>

  <!-- CSLL e IRPJ -->
  <div class="text-subtitle1 text-weight-bold q-my-md bg-primary text-white q-pa-sm">
    <q-icon name="gavel" size="sm" class="q-mr-xs" />
    CSLL
  </div>

  <div class="row q-col-gutter-md">

    <div class="col-4">
      <q-input v-model.number="editingItem.csllbase" label="Base" outlined type="number" step="0.01" min="0" prefix="R$"
        :disable="notaBloqueada" input-class="text-right" />
    </div>

    <div class="col-4">
      <q-input v-model.number="editingItem.csllpercentual" label="Alíquota" outlined type="number" step="0.01" min="0"
        max="100" suffix="%" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <div class="col-4">
      <q-input v-model.number="editingItem.csllvalor" label="Valor CSLL" outlined type="number" step="0.01" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>
  </div>

  <!-- IRPJ -->
  <div class="text-subtitle1 text-weight-bold q-my-md bg-primary text-white q-pa-sm">
    <q-icon name="gavel" size="sm" class="q-mr-xs" />
    IRPJ
  </div>

  <div class="row q-col-gutter-md">

    <div class="col-4">
      <q-input v-model.number="editingItem.irpjbase" label="Base " outlined type="number" step="0.01" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <div class="col-4">
      <q-input v-model.number="editingItem.irpjpercentual" label="Alíquota" outlined type="number" step="0.01" min="0"
        max="100" suffix="%" :disable="notaBloqueada" input-class="text-right" />
    </div>

    <div class="col-4">
      <q-input v-model.number="editingItem.irpjvalor" label="Valor IRPJ" outlined type="number" step="0.01" min="0"
        prefix="R$" :disable="notaBloqueada" input-class="text-right" />
    </div>
  </div>

</template>
