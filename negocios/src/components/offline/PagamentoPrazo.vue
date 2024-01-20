<script setup>
import { ref, watch } from "vue";
import { Notify } from "quasar";
import { negocioStore } from "stores/negocio";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sNegocio = negocioStore();

const parcelamentoDisponivel = ref([]);
const formPrazo = ref(null);
const pagamento = ref({
  valor: null,
  codformapagamento: null,
  parcelas: 1,
});

watch(
  () => pagamento.value.codformapagamento,
  () => {
    calcularParcelas();
  }
);

watch(
  () => pagamento.value.valor,
  () => {
    calcularParcelas();
  }
);

const inicializarValores = () => {
  pagamento.value.valor = sNegocio.valorapagar;
  pagamento.value.codformapagamento = process.env.CODFORMAPAGAMENTO_ENTREGA;
  pagamento.value.parcelas = 1;
  calcularParcelas();
};

const formas = ref([
  {
    codformapagamento: process.env.CODFORMAPAGAMENTO_ENTREGA,
    nome: "Acerto na Entrega",
    icone: "delivery_dining",
    valorMinimoParcela: 0,
    valorMinimo: 0,
    maximoParcelas: 1,
    maximoParcelasSemJuros: 1,
    parcelaDez: false,
    tipo: 5, // Credito Loja
  },
  {
    codformapagamento: process.env.CODFORMAPAGAMENTO_FECHAMENTO,
    nome: "Fechamento",
    icone: "calendar_month",
    valorMinimoParcela: 0,
    valorMinimo: 0,
    maximoParcelas: 1,
    maximoParcelasSemJuros: 1,
    parcelaDez: false,
    tipo: 5, // Credito Loja
  },
  {
    codformapagamento: process.env.CODFORMAPAGAMENTO_BOLETO,
    nome: "Boleto",
    icone: "account_balance",
    valorMinimoParcela: 100,
    valorMinimo: 70,
    maximoParcelas: 6,
    maximoParcelasSemJuros: 4,
    parcelaDez: true,
    tipo: 15, // Boleto Bancario
  },
  {
    codformapagamento: process.env.CODFORMAPAGAMENTO_CARTEIRA,
    nome: "Carteira",
    icone: "wallet",
    valorMinimoParcela: 50,
    valorMinimo: 30,
    maximoParcelas: 4,
    maximoParcelasSemJuros: 4,
    parcelaDez: true,
    tipo: 5, // Credito Loja
  },
]);

const valorRule = [
  (value) => {
    if (!value) {
      return "Preencha o valor!";
    }
    if (parseFloat(value) <= 0.01) {
      return "Preencha o valor!";
    }
    if (parseFloat(value) > sNegocio.valorapagar) {
      return "Valor maior que o saldo do Negócio!";
    }
    return true;
  },
];

const calcularParcelas = async () => {
  const taxa = 1.5;
  const valor = pagamento.value.valor;
  const codformapagamento = pagamento.value.codformapagamento;

  const fp = formas.value.find(
    (el) => el.codformapagamento === codformapagamento
  );

  const valorMinimoParcela = fp.valorMinimoParcela;
  const valorMinimo = fp.valorMinimo;
  const maximoParcelas = fp.maximoParcelas;
  const maximoParcelasSemJuros = fp.maximoParcelasSemJuros;

  parcelamentoDisponivel.value = [];

  for (let i = 1; i <= maximoParcelas; i++) {
    var valorjuros = 0;
    var valorparcela = Math.round(((valor + valorjuros) / i) * 100) / 100;
    if (i > maximoParcelasSemJuros) {
      valorjuros = Math.round(taxa * i * valor) / 100;
      valorparcela = Math.round(((valor + valorjuros) / i) * 100) / 100;
      valorjuros = Math.round((valorparcela * i - valor) * 100) / 100;
    }
    if (valorparcela < valorMinimoParcela) {
      break;
    }
    parcelamentoDisponivel.value.push({
      parcelas: i,
      valorjuros: valorjuros,
      valorparcela: valorparcela,
    });
  }
  pagamento.value.parcelas = 1;
};

const calcularJuros = () => {
  var parc = parcelamentoDisponivel.value.find(
    (i) => i.parcelas == pagamento.value.parcelas
  );
  pagamento.value.valorjuros = parc.valorjuros;
  pagamento.value.valorparcela = parc.valorparcela;
};

const salvar = async () => {
  sNegocio.dialog.pagamentoPrazo = false;
  var parc = parcelamentoDisponivel.value.find(
    (i) => i.parcelas == pagamento.value.parcelas
  );
  var forma = formas.value.find(
    (i) => i.codformapagamento == pagamento.value.codformapagamento
  );
  console.log(pagamento.value.codformapagamento);
  sNegocio.adicionarPagamento(
    parseInt(pagamento.value.codformapagamento), // codformapagamento Prazo
    forma.tipo, // tipo Deposito Bancario
    pagamento.value.valor, // valorpagamento
    parc.valorjuros, // valorjuros
    null, // valortroco
    null, // codpessoa
    null, // bandeira
    null, // autorizacao
    parc.parcelas, // parcelas
    parc.valorparcela // valorparcela
  );
};
</script>
<template>
  <!-- DIALOG -->
  <q-dialog
    v-model="sNegocio.dialog.pagamentoPrazo"
    @before-show="inicializarValores()"
  >
    <q-card style="width: 600px">
      <q-form @submit="salvar()" ref="formPrazo">
        <q-card-section>
          <q-list>
            <!-- VALOR -->
            <q-item>
              <q-item-section>
                <q-input
                  prefix="R$"
                  type="number"
                  step="0.01"
                  min="0.01"
                  :max="sNegocio.valorapagar"
                  borderless
                  v-model.number="pagamento.valor"
                  :rules="valorRule"
                  autofocus
                  input-class="text-h2 text-weight-bolder text-right text-primary"
                />
              </q-item-section>
            </q-item>

            <q-item>
              <div class="row">
                <!-- FORMA -->
                <div class="col-xs-12 col-sm-6">
                  <div class="row">
                    <q-radio
                      class="col-12"
                      v-model="pagamento.codformapagamento"
                      v-for="forma in formas"
                      :val="forma.codformapagamento"
                      :key="forma.codformapagamento"
                    >
                      <q-avatar :icon="forma.icone" />
                      {{ forma.nome }}
                    </q-radio>
                  </div>
                </div>

                <!-- PARCELAS -->
                <div class="col-xs-12 col-sm-6">
                  <div class="row">
                    <q-radio
                      v-model="pagamento.parcelas"
                      v-for="parc in parcelamentoDisponivel"
                      :val="parc.parcelas"
                      :key="parc.parcelas"
                      class="col-12"
                    >
                      <b>{{ parc.parcelas }}</b>
                      <span class="text-grey"> x R$ </span>
                      <b>
                        {{
                          new Intl.NumberFormat("pt-BR", {
                            style: "decimal",
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                          }).format(parc.valorparcela)
                        }}
                      </b>
                      <span v-if="parc.valorjuros"> C/Juros </span>
                    </q-radio>
                  </div>
                </div>
              </div>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="primary"
            @click="sNegocio.dialog.pagamentoPrazo = false"
            tabindex="-1"
          />
          <q-btn type="submit" flat label="salvar" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
