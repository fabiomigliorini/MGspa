<script setup>
import { ref, watch, computed } from "vue";
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
    abonarJurosAcima: 0,
    parcelaDez: false,
    tipo: 5, // Credito Loja
  },
  {
    codformapagamento: process.env.CODFORMAPAGAMENTO_FECHAMENTO,
    nome: "Fechamento",
    icone: "calendar_month",
    valorMinimoParcela: 50,
    valorMinimo: 0,
    maximoParcelas: 4,
    maximoParcelasSemJuros: 4,
    abonarJurosAcima: 0,
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
    abonarJurosAcima: 500,
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
    abonarJurosAcima: 0,
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

const forma = computed({
  get() {
    if (!pagamento.value.codformapagamento) {
      return null;
    }
    return formas.value.find(
      (el) => el.codformapagamento === pagamento.value.codformapagamento
    );
  },
});

const isEntrega = computed({
  get() {
    return (
      pagamento.value.codformapagamento == process.env.CODFORMAPAGAMENTO_ENTREGA
    );
  },
});

const isFechamento = computed({
  get() {
    return (
      pagamento.value.codformapagamento ==
      process.env.CODFORMAPAGAMENTO_FECHAMENTO
    );
  },
});

const isCarteira = computed({
  get() {
    return (
      pagamento.value.codformapagamento ==
      process.env.CODFORMAPAGAMENTO_CARTEIRA
    );
  },
});

const isBoleto = computed({
  get() {
    return (
      pagamento.value.codformapagamento == process.env.CODFORMAPAGAMENTO_BOLETO
    );
  },
});

const calcularParcelas = async () => {
  const taxa = 1.5;
  const valor = pagamento.value.valor;
  const codformapagamento = pagamento.value.codformapagamento;

  const valorMinimoParcela = forma.value.valorMinimoParcela;
  const valorMinimo = forma.value.valorMinimo;
  const maximoParcelas = forma.value.maximoParcelas;
  const maximoParcelasSemJuros = forma.value.maximoParcelasSemJuros;
  const abonarJurosAcima = forma.value.abonarJurosAcima;

  parcelamentoDisponivel.value = [];
  pagamento.value.parcelas = 0;

  if (valor < valorMinimo) {
    return;
  }

  for (let i = 1; i <= maximoParcelas; i++) {
    var valorjuros = 0;
    var valorparcela = Math.round(((valor + valorjuros) / i) * 100) / 100;
    if (i > maximoParcelasSemJuros && valorparcela < abonarJurosAcima) {
      valorjuros = Math.round(taxa * i * valor) / 100;
      valorparcela = Math.round(((valor + valorjuros) / i) * 100) / 100;
      valorjuros = Math.round((valorparcela * i - valor) * 100) / 100;
    }
    if (valorparcela < valorMinimoParcela && i > 1) {
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

const salvar = async () => {
  if (
    pagamento.value.codformapagamento !=
      process.env.CODFORMAPAGAMENTO_ENTREGA &&
    sNegocio.negocio.codpessoa == 1
  ) {
    Notify.create({
      type: "negative",
      message:
        "Não é possível adicionar pagamento à Prazo para Consumidor! Informe o Cliente primeiro!",
      actions: [{ icon: "close", color: "white" }],
    });
    return;
  }
  sNegocio.dialog.pagamentoPrazo = false;
  var parc = parcelamentoDisponivel.value.find(
    (i) => i.parcelas == pagamento.value.parcelas
  );
  var forma = formas.value.find(
    (i) => i.codformapagamento == pagamento.value.codformapagamento
  );
  sNegocio.adicionarPagamento(
    parseInt(pagamento.value.codformapagamento), // codformapagamento Prazo
    forma.tipo, // tipo Deposito Bancario
    null, // codtitulo
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

                <!-- PARCELAMENTO -->
                <div class="col-xs-12 col-sm-6">
                  <!-- OBSERVACOES -->
                  <div class="row text-caption text-grey-8" v-if="isEntrega">
                    Cliente vai pagar na entrega.
                  </div>
                  <div class="row text-caption text-grey-8" v-if="isFechamento">
                    Financeiro cuidará da cobrança.
                  </div>
                  <div class="row text-caption text-grey-8" v-if="isCarteira">
                    Mínimo: R$ 30,00. <br />
                    Parcelado: R$ 50,00/Parcela. <br />
                    Até 4 Parcelas. <br />
                  </div>
                  <div class="row text-caption text-grey-8" v-if="isBoleto">
                    Mínimo: R$ 70,00. <br />
                    Parcelado: R$ 100,00/Parcela. <br />
                    Até 4 Parcelas Sem Juros. <br />
                    Até 6 Parcelas Com Juros. <br />
                    Parcelas acima de R$ 500,00 abonam juros. <br />
                  </div>
                  <!-- PARCELAS -->
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
          <q-btn
            type="submit"
            flat
            label="salvar"
            color="primary"
            :disable="pagamento.parcelas < 1"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
