<script setup>
// Vale colaborador e adiantamentos no PDV (M8 do plano doc-3): tipo, pessoa, valor, conta e
// vencimento → wizard de cobrança de @components no sentido do tipo (vale e adiantamento a
// fornecedor saem da gaveta em dinheiro; adiantamento de cliente entra como no Receber título).
// Cada forma lançada vira um título; quando zera, grava e imprime o comprovante com assinatura.
import { computed, watch } from 'vue'
import { pagamentoStore, TIPOS_LANCAMENTO } from 'stores/pagamento'
import { baixaTitulosStore } from '@components/stores/baixaTitulosStore'
import { formataNumero } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectContaContabil from '@components/MgSelectContaContabil.vue'
import SelectPessoa from 'components/selects/SelectPessoa.vue'

const sPagamento = pagamentoStore()
const sBaixa = baixaTitulosStore()

const lancou = computed(() => sBaixa.pagamentos.length > 0)
const verbo = computed(() => (sPagamento.tipoLancamento?.entrada ? 'Receber' : 'Pagar'))

// lançou tudo: grava sozinho
watch(
  () => [sBaixa.saldo, sBaixa.pagamentos.length],
  async ([saldo, lancados]) => {
    if (sPagamento.dialogLancamento && lancados > 0 && Math.abs(saldo) < 0.005) {
      await sPagamento.finalizarLancamento()
    }
  },
)
</script>

<template>
  <q-dialog v-model="sPagamento.dialogLancamento">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="sPagamento.cobrarLancamento()">
        <q-card-section>
          <div class="text-h6">Vale / Adiantamento</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-option-group
                :model-value="sPagamento.lancamento.codtipotitulo"
                :options="TIPOS_LANCAMENTO"
                :disable="lancou"
                inline
                @update:model-value="sPagamento.trocarTipoLancamento"
              />
            </div>
            <div class="col-12">
              <select-pessoa
                outlined
                v-model="sPagamento.lancamento.codpessoa"
                label="Pessoa"
                autofocus
                :disable="lancou"
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputValor
                v-model="sPagamento.lancamento.valor"
                label="Valor"
                prefix="R$"
                :min="0.01"
                :disable="lancou"
                :rules="[(v) => v > 0]"
                lazy-rules
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputData
                v-model="sPagamento.lancamento.vencimento"
                label="Vencimento"
                :disable="lancou"
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <div class="col-12">
              <MgSelectContaContabil
                v-model="sPagamento.lancamento.codcontacontabil"
                :disable="lancou"
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>
            <div class="col-12">
              <MgInput
                v-model="sPagamento.lancamento.observacao"
                label="Observação"
                maxlength="255"
                :disable="lancou"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-section v-if="lancou" class="q-pt-none">
          <div
            v-for="(p, i) in sBaixa.pagamentos"
            :key="i"
            class="row items-center text-body2 text-grey-8"
          >
            <div class="col">{{ p.descricao }}</div>
            <div>R$ {{ formataNumero(p.total) }}</div>
            <q-btn
              v-if="!p.codpagamento"
              flat
              round
              size="sm"
              icon="close"
              color="grey-7"
              @click="sBaixa.remover(i)"
            />
          </div>
          <div class="row items-center text-subtitle1 text-orange-10">
            <div class="col">Falta</div>
            <div>R$ {{ formataNumero(sBaixa.saldo) }}</div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat color="grey-8" label="Cancelar" v-close-popup />
          <q-btn
            v-if="lancou && sBaixa.saldo > 0"
            flat
            color="primary"
            label="Lançar o que foi pago"
            :loading="sBaixa.finalizando"
            @click="sPagamento.finalizarLancamento()"
          />
          <q-btn flat color="primary" :label="verbo" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
