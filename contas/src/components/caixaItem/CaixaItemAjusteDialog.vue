<script setup>
// Ajuste na conta corrente da maquineta de parceiro: aumenta ou diminui o que devemos ao parceiro,
// com a observação obrigatória (comissão que o parceiro desconta, saldo inicial, diferença com o
// relatório dele). Não gera título nem mexe em caixa; só se cancela, com justificativa.
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { useCaixaItemStore } from 'src/stores/caixaItemStore'

const SENTIDOS = [
  { label: 'Aumenta o que devemos', value: 1 },
  { label: 'Diminui o que devemos', value: -1 },
]

const store = useCaixaItemStore()
const { ajusteDialog, ajusteModel, salvando } = storeToRefs(store)
</script>

<template>
  <q-dialog v-model="ajusteDialog">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">AJUSTE NA CONTA CORRENTE</q-card-section>
      <q-form @submit.prevent="store.ajustarConta">
        <q-separator inset />
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <MgInputData
                v-model="ajusteModel.transacao"
                type="timestamp"
                default-time="now"
                label="Data"
                :rules="[(v) => !!v || 'Obrigatório']"
              />
            </div>
            <div class="col-12">
              <MgInput
                v-model="ajusteModel.observacoes"
                label="Motivo do ajuste"
                type="textarea"
                autofocus
                autogrow
                maxlength="300"
                :rules="[(v) => (v || '').trim().length >= 3 || 'Diga o motivo']"
                lazy-rules
              />
            </div>
            <div class="col-12">
              <!-- o q-option-group não tem rules: o q-field sem borda valida a escolha -->
              <q-field
                v-model="ajusteModel.sinal"
                borderless
                :rules="[(v) => !!v || 'Informe se aumenta ou diminui']"
              >
                <template #control>
                  <q-option-group
                    v-model="ajusteModel.sinal"
                    type="radio"
                    inline
                    :options="SENTIDOS"
                  />
                </template>
              </q-field>
            </div>
            <div class="col-12">
              <MgInputValor
                v-model="ajusteModel.valor"
                label="Valor"
                :rules="[(v) => v > 0 || 'Informe o valor']"
                lazy-rules
              />
            </div>
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Lançar" color="primary" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
