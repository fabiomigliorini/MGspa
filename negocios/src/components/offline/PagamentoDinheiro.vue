<script setup>
import { formataNumero } from "@components/formatters";
import { ref, computed } from "vue";
import { negocioStore } from "stores/negocio";
import MgInputValor from "@components/MgInputValor.vue";
const sNegocio = negocioStore();

const valorPagamento = ref(null);

const valorSaldo = computed(() => {
  return sNegocio.valorapagar - valorPagamento.value;
});

const valorSaldoClass = computed(() => {
  return valorSaldo.value > 0 ? "text-red" : "text-green";
});

const valorSaldoLabel = computed(() => {
  return valorSaldo.value > 0 ? "Faltando" : "Troco";
});

const inicializarValores = () => {
  valorPagamento.value = null;
};

const maiorQueZeroRule = [
  (value) => {
    if (parseFloat(value) > 0) {
      return true;
    }
    return false;
  },
];

const salvar = () => {
  var valortroco = null;
  if (valorSaldo.value < 0) {
    valortroco = Math.round(Math.abs(valorSaldo.value * 100)) / 100;
  }
  sNegocio.dialog.pagamentoDinheiro = false;
  sNegocio.adicionarPagamento(
    parseInt(process.env.CODFORMAPAGAMENTO_DINHEIRO), // codformapagamento Dinheiro
    1, // tipo Dinheiro
    null, // codtitulo
    valorPagamento.value,
    null, // valorjuros
    valortroco,
    null, // codpessoa
    null, // bandeira
    null, // autorizacao
    null, // parcelas
    null, // valorparcela
    null // dias // valorparcela
  );
};
</script>
<template>
  <q-dialog
    v-model="sNegocio.dialog.pagamentoDinheiro"
    @before-show="inicializarValores()"
  >
    <q-card>
      <q-form @submit="salvar()">
        <q-card-section>
          <q-list>
            <q-item>
              <q-item-section side class="text-h5 text-grey">
                R$
              </q-item-section>
              <q-item-section>
                <q-item-label
                  class="text-h2 text-primary text-weight-bolder text-right"
                >
                  {{
                    formataNumero(sNegocio.valorapagar)
                  }}
                </q-item-label>
                <q-item-label caption class="text-right">
                  À Pagar
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section side class="text-h5 text-grey">
                R$
              </q-item-section>
              <q-item-section>
                <q-item-label
                  class="text-h2 text-primary text-weight-bolder text-right"
                >
                  <MgInputValor
                    :min="0"
                    v-model="valorPagamento"
                    :rules="maiorQueZeroRule"
                    autofocus
                    class="q-input-grande text-h4"
                    input-class="text-weight-bold text-h2 text-primary"
                    :borderless="true"
                    :outlined="false"
                  />
                </q-item-label>
                <q-item-label caption class="text-right">
                  Pagamento
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section side class="text-h5 text-grey">
                R$
              </q-item-section>
              <q-item-section>
                <q-item-label
                  :class="valorSaldoClass"
                  class="text-h2 text-weight-bolder text-right"
                >
                  {{
                    formataNumero(Math.abs(valorSaldo))
                  }}
                </q-item-label>
                <q-item-label caption class="text-right">
                  {{ valorSaldoLabel }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="primary"
            @click="sNegocio.dialog.pagamentoDinheiro = false"
            tabindex="-1"
          />
          <q-btn type="submit" flat label="Salvar" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
