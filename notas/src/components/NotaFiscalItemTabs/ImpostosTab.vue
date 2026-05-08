<script setup>
import { computed } from 'vue'
import { useNotaFiscalStore } from 'src/stores/notaFiscalStore'
import {
  ICMS_CST_OPTIONS,
  CSOSN_OPTIONS,
  IPI_CST_OPTIONS,
  PIS_CST_OPTIONS,
  COFINS_CST_OPTIONS,
} from 'src/constants/notaFiscal'
import { storeToRefs } from 'pinia'
import SelectCfop from '../selects/SelectCfop.vue'
import MgInputValor from '@components/MgInputValor.vue'

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
        <div class="col-6 col-sm-4">
          <SelectCfop
            v-model="editingItem.codcfop"
            label="CFOP *"
            :disable="notaBloqueada"
          />
        </div>

        <!-- CST / CSOSN -->
        <div class="col-6 col-sm-8" v-if="!editingItem.csosn">
          <q-select
            v-model="editingItem.icmscst"
            :options="ICMS_CST_OPTIONS"
            label="CST"
            outlined
            emit-value
            map-options
            clearable
            :disable="notaBloqueada"
          />
        </div>

        <div class="col-6 col-sm-8" v-else>
          <q-select
            v-model="editingItem.csosn"
            :options="CSOSN_OPTIONS"
            label="CSOSN "
            hint="Simples Nacional"
            outlined
            emit-value
            map-options
            clearable
            :disable="notaBloqueada"
          />
        </div>

        <!-- Base de Cálculo -->
        <div class="col-6 col-sm-3">
          <MgInputValor
            v-model="editingItem.icmsbasepercentual"
            label="% da Base"
            :min="0"
            :max="100"
            suffix="%"
            :readonly="notaBloqueada"
          />
        </div>

        <div class="col-6 col-sm-3">
          <MgInputValor
            v-model="editingItem.icmsbase"
            label="Base de Cálculo"
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>

        <!-- Alíquota -->
        <div class="col-6 col-sm-3">
          <MgInputValor
            v-model="editingItem.icmspercentual"
            label="Alíquota"
            :min="0"
            :max="100"
            suffix="%"
            :readonly="notaBloqueada"
          />
        </div>

        <!-- Valor ICMS -->
        <div class="col-6 col-sm-3">
          <MgInputValor
            v-model="editingItem.icmsvalor"
            label="Valor ICMS"
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>
      </div>
    </q-card-section>
  </q-card>

  <!-- ICMS ST -->
  <q-card flat bordered class="q-mb-md full-height">
    <q-card-section class="text-subtitle1 text-weight-bold bg-primary text-white q-pa-sm">
      <q-icon name="account_balance" size="sm" class="q-mr-xs" />
      ICMS ST (Substituição Tributária)
    </q-card-section>

    <q-card-section>
      <div class="row q-col-gutter-md">
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.icmsstbase"
            label="Base "
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>

        <div class="col-4">
          <MgInputValor
            v-model="editingItem.icmsstpercentual"
            label="Alíquota "
            :min="0"
            :max="100"
            suffix="%"
            :readonly="notaBloqueada"
          />
        </div>

        <div class="col-4">
          <MgInputValor
            v-model="editingItem.icmsstvalor"
            label="Valor ST"
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>
      </div>
    </q-card-section>
  </q-card>

  <!-- IPI -->
  <q-card flat bordered class="q-mb-md full-height">
    <q-card-section class="text-subtitle1 text-weight-bold bg-primary text-white q-pa-sm">
      <q-icon name="inventory" size="sm" class="q-mr-xs" />
      IPI
    </q-card-section>
    <q-card-section>
      <div class="row q-col-gutter-md">
        <!-- CST -->
        <div class="col-12">
          <q-select
            v-model="editingItem.ipicst"
            :options="IPI_CST_OPTIONS"
            label="IPI CST"
            outlined
            emit-value
            map-options
            clearable
            :disable="notaBloqueada"
          />
        </div>

        <!-- Base de Cálculo -->
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.ipibase"
            label="Base "
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>

        <!-- Alíquota -->
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.ipipercentual"
            label="Alíquota"
            :min="0"
            :max="100"
            suffix="%"
            :readonly="notaBloqueada"
          />
        </div>

        <!-- Valor IPI -->
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.ipivalor"
            label="Valor IPI"
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>

        <!-- Valor Devolução -->
        <div class="col-6">
          <MgInputValor
            v-model="editingItem.ipidevolucaovalor"
            label="IPI Devolvido "
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>

        <!-- Devolução Percentual -->
        <div class="col-6">
          <MgInputValor
            v-model="editingItem.devolucaopercentual"
            label="% Devolução"
            :min="0"
            :max="100"
            suffix="%"
            :readonly="notaBloqueada"
          />
        </div>
      </div>
    </q-card-section>
  </q-card>

  <!-- PIS -->
  <q-card flat bordered class="q-mb-md full-height">
    <q-card-section class="text-subtitle1 text-weight-bold bg-primary text-white q-pa-sm">
      <q-icon name="money" size="sm" class="q-mr-xs" />
      PIS
    </q-card-section>
    <q-card-section>
      <div class="row q-col-gutter-md">
        <!-- CST -->
        <div class="col-12">
          <q-select
            v-model="editingItem.piscst"
            :options="PIS_CST_OPTIONS"
            label="PIS CST"
            outlined
            emit-value
            map-options
            clearable
            :disable="notaBloqueada"
          />
        </div>

        <!-- Base -->
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.pisbase"
            label="Base"
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>

        <!-- Alíquota -->
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.pispercentual"
            label="Alíquota"
            :min="0"
            :max="100"
            suffix="%"
            :readonly="notaBloqueada"
          />
        </div>

        <!-- Valor -->
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.pisvalor"
            label="Valor PIS"
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>
      </div>
    </q-card-section>
  </q-card>

  <!-- COFINS -->
  <q-card flat bordered class="q-mb-md full-height">
    <q-card-section class="text-subtitle1 text-weight-bold bg-primary text-white q-pa-sm">
      <q-icon name="attach_money" size="sm" class="q-mr-xs" />
      COFINS
    </q-card-section>
    <q-card-section>
      <div class="row q-col-gutter-md">
        <!-- CST -->
        <div class="col-12">
          <q-select
            v-model="editingItem.cofinscst"
            :options="COFINS_CST_OPTIONS"
            label="COFINS CST"
            outlined
            emit-value
            map-options
            clearable
            :disable="notaBloqueada"
          />
        </div>

        <!-- Base -->
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.cofinsbase"
            label="Base de Cálculo"
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>

        <!-- Alíquota -->
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.cofinspercentual"
            label="Alíquota COFINS"
            :min="0"
            :max="100"
            suffix="%"
            :readonly="notaBloqueada"
          />
        </div>

        <!-- Valor -->
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.cofinsvalor"
            label="Valor COFINS"
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>
      </div>
    </q-card-section>
  </q-card>

  <!-- CSLL e IRPJ -->
  <q-card flat bordered class="q-mb-md full-height">
    <q-card-section class="text-subtitle1 text-weight-bold bg-primary text-white q-pa-sm">
      <q-icon name="gavel" size="sm" class="q-mr-xs" />
      CSLL
    </q-card-section>
    <q-card-section>
      <div class="row q-col-gutter-md">
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.csllbase"
            label="Base"
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>

        <div class="col-4">
          <MgInputValor
            v-model="editingItem.csllpercentual"
            label="Alíquota"
            :min="0"
            :max="100"
            suffix="%"
            :readonly="notaBloqueada"
          />
        </div>

        <div class="col-4">
          <MgInputValor
            v-model="editingItem.csllvalor"
            label="Valor CSLL"
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>
      </div>
    </q-card-section>
  </q-card>

  <!-- IRPJ -->
  <q-card flat bordered class="q-mb-md full-height">
    <q-card-section class="text-subtitle1 text-weight-bold bg-primary text-white q-pa-sm">
      <q-icon name="gavel" size="sm" class="q-mr-xs" />
      IRPJ
    </q-card-section>
    <q-card-section>
      <div class="row q-col-gutter-md">
        <div class="col-4">
          <MgInputValor
            v-model="editingItem.irpjbase"
            label="Base "
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>

        <div class="col-4">
          <MgInputValor
            v-model="editingItem.irpjpercentual"
            label="Alíquota"
            :min="0"
            :max="100"
            suffix="%"
            :readonly="notaBloqueada"
          />
        </div>

        <div class="col-4">
          <MgInputValor
            v-model="editingItem.irpjvalor"
            label="Valor IRPJ"
            :min="0"
            prefix="R$"
            :readonly="notaBloqueada"
          />
        </div>
      </div>
    </q-card-section>
  </q-card>
</template>
