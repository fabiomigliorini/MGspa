<script setup>
import { ref, computed } from "vue";
import { negocioStore } from "stores/negocio";
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
    1010, // codformapagamento Dinheiro
    1, // tipo Dinheiro
    valorPagamento.value,
    null, // valorjuros
    valortroco,
    false, // integracao
    null, // codpessoa
    null, // bandeira
    null, // autorizacao
    null, // codpixcob
    null // codpagarmepedido
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
                    new Intl.NumberFormat("pt-BR", {
                      style: "decimal",
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    }).format(sNegocio.valorapagar)
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
                  <q-input
                    type="number"
                    step="0.01"
                    min="0"
                    borderless
                    v-model.number="valorPagamento"
                    :rules="maiorQueZeroRule"
                    autofocus
                    input-class="text-h2 text-weight-bolder text-right text-primary"
                  >
                    <template v-slot:error>
                      <div class="text-right">Valor inválido!</div>
                    </template>
                  </q-input>
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
                  <small class="text-h5 text-grey">R$ </small>
                  {{
                    new Intl.NumberFormat("pt-BR", {
                      style: "decimal",
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    }).format(Math.abs(valorSaldo))
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
