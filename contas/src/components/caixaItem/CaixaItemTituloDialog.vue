<script setup>
// Gerar o título a pagar ao parceiro pela conta corrente da maquineta: o valor (sugere o saldo) e
// o vencimento (sugere hoje). O título nasce em aberto, sem portador, com o parceiro, a filial e a
// conta contábil da maquineta, e é pago pelo caminho normal do contas. Só gera o título e o
// débito ligado a ele.
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { formataNumero } from '@components/formatters'
import { useCaixaItemStore } from 'src/stores/caixaItemStore'

const store = useCaixaItemStore()
const { item, conta, tituloDialog, tituloModel, salvando } = storeToRefs(store)
</script>

<template>
  <q-dialog v-model="tituloDialog">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">GERAR TÍTULO A PAGAR</q-card-section>
      <q-form @submit.prevent="store.gerarTitulo">
        <q-separator inset />
        <q-card-section class="text-caption text-grey-7 q-pb-none">
          Para {{ item?.pessoa }} ({{ item?.filial }}, {{ item?.contacontabil }}). Saldo a pagar: R$
          {{ formataNumero(conta?.saldo ?? 0) }}.
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <MgInputValor
                v-model="tituloModel.valor"
                label="Valor"
                autofocus
                :rules="[(v) => v > 0 || 'Informe o valor']"
                lazy-rules
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputData
                v-model="tituloModel.vencimento"
                label="Vencimento"
                :rules="[(v) => !!v || 'Obrigatório']"
              />
            </div>
            <div class="col-12">
              <MgInput v-model="tituloModel.observacoes" label="Observação" maxlength="200" />
            </div>
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Gerar título" color="primary" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
