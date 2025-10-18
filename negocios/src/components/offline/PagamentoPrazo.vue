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
    nome: "Pagamento na Entrega",
    icone: "delivery_dining",
    valorMinimoParcela: 0,
    valorMinimo: 0,
    maximoParcelas: 1,
    maximoParcelasSemJuros: 1,
    abonarJurosAcima: 0,
    parcelaDez: false,
    tipo: 5, // Credito Loja
    diasAvulsos: []
  },
  {
    codformapagamento: process.env.CODFORMAPAGAMENTO_FECHAMENTO,
    nome: "Fechamento Mensal",
    icone: "calendar_month",
    valorMinimoParcela: 50,
    valorMinimo: 0,
    maximoParcelas: 4,
    maximoParcelasSemJuros: 4,
    abonarJurosAcima: 0,
    parcelaDez: false,
    tipo: 5, // Credito Loja
    diasAvulsos: []
  },
  {
    codformapagamento: process.env.CODFORMAPAGAMENTO_BOLETO,
    nome: "Emitir Boleto",
    icone: "account_balance",
    valorMinimoParcela: 100,
    valorMinimo: 70,
    maximoParcelas: 4,
    maximoParcelasSemJuros: 4,
    abonarJurosAcima: 0,
    parcelaDez: true,
    tipo: 15, // Boleto Bancario
    diasAvulsos: [7, 10, 15]
  },
  {
    codformapagamento: process.env.CODFORMAPAGAMENTO_CARTEIRA,
    nome: "Crediário",
    icone: "wallet",
    valorMinimoParcela: 50,
    valorMinimo: 30,
    maximoParcelas: 4,
    maximoParcelasSemJuros: 4,
    abonarJurosAcima: 0,
    parcelaDez: true,
    tipo: 5, // Credito Loja
    diasAvulsos: []
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

  if (forma.value.codformapagamento == process.env.CODFORMAPAGAMENTO_ENTREGA) {
    parcelamentoDisponivel.value.push({
      parcelas: 1,
      valorjuros: 0,
      valorparcela: valor,
      label: 'Cliente paga na entrega',
      dias: 0
    });
    pagamento.value.parcelas = 0;
    return;
  }

  let i = 0;
  for (let i of forma.value.diasAvulsos) {
    parcelamentoDisponivel.value.push({
      parcelas: 1,
      valorjuros: 0,
      valorparcela: valor,
      label: i + ' Dias',
      dias: i
    });
  }
  pagamento.value.parcelas = parcelamentoDisponivel.value.length;
  let label = '';
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
    if (forma.value.codformapagamento == process.env.CODFORMAPAGAMENTO_FECHAMENTO) {
      if (i > 1) {
        label = label.slice(0, -3) + '/'
      }
      label += moment().add(5, 'days').add(i, 'month').endOf('month').format('MMM/YY');
    } else {
      if (i > 1) {
        label = label.slice(0, -5) + '/'
      }
      label += i * 30 + ' Dias'
    }
    parcelamentoDisponivel.value.push({
      parcelas: i,
      valorjuros: valorjuros,
      valorparcela: valorparcela,
      label: label,
      dias: 30
    });
  }
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
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    return;
  }
  sNegocio.dialog.pagamentoPrazo = false;
  // var parc = parcelamentoDisponivel.value.find(
  //   (i) => i.parcelas == pagamento.value.parcelas
  // );
  var parc = parcelamentoDisponivel.value[pagamento.value.parcelas];
  var forma = formas.value.find(
    (i) => i.codformapagamento == pagamento.value.codformapagamento
  );
  console.log(forma);
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
    parc.valorparcela, // valorparcela
    parc.dias // dias
  );
};
</script>
<template>
  <!-- DIALOG -->
  <q-dialog v-model="sNegocio.dialog.pagamentoPrazo" @before-show="inicializarValores()" class="full-width">
    <q-card style="width: 700px; max-width: 80vw;">
      <q-form @submit="salvar()" ref="formPrazo">
        <q-card-section>
          <q-list>
            <!-- VALOR -->
            <q-item>
              <q-item-section>
                <q-input prefix="R$" type="number" step="0.01" min="0.01" :max="sNegocio.valorapagar" borderless
                  v-model.number="pagamento.valor" :rules="valorRule" autofocus
                  input-class="text-h2 text-weight-bolder text-right text-primary" />
              </q-item-section>
            </q-item>
            <q-item>
              <div class="row">
                <!-- FORMA -->
                <div class="col-xs-12 col-sm-6">
                  <div class="row">
                    <q-radio class="col-12" v-model="pagamento.codformapagamento" v-for="forma in formas"
                      :val="forma.codformapagamento" :key="forma.codformapagamento">
                      <q-avatar :icon="forma.icone" />
                      {{ forma.nome }}
                    </q-radio>
                  </div>
                </div>

                <!-- PARCELAMENTO -->
                <div class="col-xs-12 col-sm-6">

                  <!-- OBSERVACOES -->
                  <div class="row text-caption text-grey-8" v-if="isEntrega">
                    Cliente vai pagar no momento da entrega ou retirada do produto.
                  </div>
                  <div class="row text-caption text-grey-8" v-if="isFechamento">
                    Financeiro cuidará da cobrança. Serão somadas todas as compras do período e emitida a Nota Fiscal
                    com todas as compras no final do mês com vencimento para o último dia do mês subsequente.
                  </div>
                  <div class="row text-caption text-grey-8" v-if="isCarteira">
                    Valor Mínimo: R$ 30,00. <br />
                    Parcelado: R$ 50,00/Parcela. <br />
                    Até 4 Parcelas. <br />
                  </div>
                  <div class="row text-caption text-grey-8" v-if="isBoleto">
                    Valor Mínimo: R$ 70,00. <br />
                    Parcelado: R$ 100,00/Parcela. <br />
                    Máximo 4 Parcelas. <br />
                  </div>

                  <!-- PARCELAS -->
                  <q-list>
                    <template v-for="(parc, i) in parcelamentoDisponivel" :key="i">
                      <q-item tag="label" v-ripple>
                        <q-item-section avatar>
                          <q-radio v-model="pagamento.parcelas" :val="i" />
                        </q-item-section>
                        <q-item-section>
                          <q-item-label class="text-bold">
                            {{ parc.label }}
                          </q-item-label>
                          <q-item-label caption>
                            {{ parc.parcelas }}
                            parcela(s) de
                            R$
                            {{
                              new Intl.NumberFormat("pt-BR", {
                                style: "decimal",
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                              }).format(parc.valorparcela)
                            }}
                          </q-item-label>
                          <q-item-label caption v-if="parc.valorjuros">
                            C/Juros
                          </q-item-label>
                        </q-item-section>
                      </q-item>
                    </template>
                  </q-list>

                </div>
              </div>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="primary" @click="sNegocio.dialog.pagamentoPrazo = false" tabindex="-1" />
          <q-btn type="submit" flat label="salvar" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
