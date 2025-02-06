<script setup>
import { ref, watch, computed } from "vue";
import { Notify, debounce } from "quasar";
import { negocioStore } from "stores/negocio";
import { pagarMeStore } from "stores/pagar-me";
import { saurusStore } from "stores/saurus";
import emitter from "../../utils/emitter.js";
import SelectPagarMePos from "../selects/SelectPagarMePos.vue";
import SelectSaurusPos from "../selects/SelectSaurusPos.vue";
import cartoesManuais from "../../data/cartoes-manuais.json";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sNegocio = negocioStore();
const sPagarMe = pagarMeStore();
const edicao = ref({});
const sSaurus = saurusStore();
const pagamento = ref({});
const parcelamentoDisponivel = ref([]);
const formSaurus = ref(null);
const stepManual = ref(1);
const btnConsultarRef = ref(null);
const opcoesPos = ref([
  { name: "POS Stone/PagarMe", value: "pagarme" },
  { name: "POS Safrapay/Saurus", value: "saurus" },
]);

const inicializarValores = () => {
  const padrao = {
    codsauruspos: sNegocio.padrao.codsauruspos,
    codpagarmepos: sNegocio.padrao.codpagarmepos,
    valor: sNegocio.valorapagar,
    valorparcela: null,
    valorjuros: null,
    tipo: 1,
    parcelas: 1,
    jurosloja: true,
    codpessoa: null,
    autorizacao: null,
    bandeira: null,
    codpessoa: null,
  };
  if (sNegocio.negocio.codestoquelocal != sNegocio.padrao.codestoquelocal) {
    padrao.codsauruspos = null;
    padrao.codpagarmepos = null;
  }
  pagamento.value = padrao;
  edicao.value = { ...sNegocio.padrao };
  calcularParcelas();
};

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

watch(
  () => pagamento.value.tipo,
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

watch(
  () => pagamento.value.parcelas,
  () => {
    calcularJuros();
  }
);

watch(
  () => pagamento.value.codpessoa,
  () => {
    if (bandeirasManuais.value.length == 1) {
      pagamento.value.bandeira = bandeirasManuais.value[0].bandeira;
    }
    if (tiposManuais.value.length == 1) {
      pagamento.value.tipo = tiposManuais.value[0].tipo;
    }
    return;
  }
);

const calcularParcelas = async () => {
  const valorMinimoParcela = 30.0;
  const taxa = 1.5;
  const valor = pagamento.value.valor;
  const tipo = pagamento.value.tipo;

  parcelamentoDisponivel.value = [];
  for (let i = 1; i <= 18; i++) {
    var valorjuros = 0;
    var valorparcela = Math.round(((valor + valorjuros) / i) * 100) / 100;
    if (i > 6) {
      valorjuros = Math.round(taxa * i * valor) / 100;
      valorparcela = Math.round(((valor + valorjuros) / i) * 100) / 100;
      valorjuros = Math.round((valorparcela * i - valor) * 100) / 100;
    }
    let habilitado = false;
    if (i == 1) {
      habilitado = true;
    } else if (tipo == 2 && valorparcela >= valorMinimoParcela && i <= 18) {
      habilitado = true;
    }
    if (habilitado) {
      parcelamentoDisponivel.value.push({
        parcelas: i,
        habilitado: habilitado,
        valorjuros: valorjuros,
        valorparcela: valorparcela,
      });
    } else {
      break;
    }
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
  const pedido = await sNegocio.criarSaurusPedido(
    pagamento.value.codsauruspos,
    pagamento.value.valor,
    pagamento.value.valorparcela,
    pagamento.value.valorjuros,
    pagamento.value.tipo,
    pagamento.value.parcelas,
    pagamento.value.jurosloja
  );
  if (pedido == false) {
    return;
  }
  sSaurus.pedido = pedido;
  sSaurus.dialog.detalhesPedido = true;
  sNegocio.dialog.pagamentoSaurus = false;
};

const validarManual = async () => {
  if (!pagamento.value.codpessoa) {
    stepManual.value = 1;
    Notify.create({
      type: "negative",
      message: "Selecione um parceiro!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    return false;
  }

  if (!pagamento.value.tipo) {
    stepManual.value = 2;
    Notify.create({
      type: "negative",
      message: "Preencha o Tipo!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    return false;
  }

  if (!pagamento.value.valor) {
    stepManual.value = 3;
    Notify.create({
      type: "negative",
      message: "Preencha o Valor!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    return false;
  }

  if (pagamento.value.valor > sNegocio.valorapagar) {
    stepManual.value = 3;
    Notify.create({
      type: "negative",
      message: "O Valor não pode ser maior que o saldo a pagar do Negócio!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    return false;
  }

  if (!pagamento.value.parcelas) {
    stepManual.value = 3;
    Notify.create({
      type: "negative",
      message: "Selecione a quantidade de Parcelas!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    return false;
  }

  if (!pagamento.value.bandeira) {
    stepManual.value = 4;
    Notify.create({
      type: "negative",
      message: "Selecione a Bandeira!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    return false;
  }

  if (!pagamento.value.autorizacao) {
    stepManual.value = 5;
    Notify.create({
      type: "negative",
      message: "Preencha o número de Autorização!",
      timeout: 3000, // 3 segundos
      actions: [{ icon: "close", color: "white" }],
    });
    return false;
  }

  return true;
};

const salvarManual = async () => {
  if (!(await validarManual())) {
    return;
  }
  const tipo = tiposManuais.value.find((el) => {
    return pagamento.value.tipo == el.tipo;
  });
  await sNegocio.adicionarPagamento(
    parseInt(process.env.CODFORMAPAGAMENTO_CARTAOMANUAL), // codformapagamento Dinheiro
    tipo.tpag, // tipo
    null, // codtitulo
    pagamento.value.valor,
    pagamento.value.valorjuros, // valorjuros
    null, // valortroco
    pagamento.value.codpessoa, // codpessoa
    pagamento.value.bandeira, // bandeira
    pagamento.value.autorizacao, // autorizacao
    pagamento.value.parcelas,
    pagamento.value.valorparcela
  );
  sNegocio.dialog.pagamentoCartaoManual = false;
};

const consultar = debounce(async () => {
  if (edicao.value.maquineta === "saurus") {
    await sSaurus.consultarPedido();
    if (sSaurus.pedido.status == 2) {
      sSaurus.dialog.detalhesPedido = false;
      emitter.emit("pagamentoAdicionado");
    }
  } else if (edicao.value.maquineta === "pagarme") {
    await sPagarMe.consultarPedido();
    if (sPagarMe.pedido.status == 2) {
      sPagarMe.dialog.detalhesPedido = false;
      emitter.emit("pagamentoAdicionado");
    }
  }
}, 500);

const cancelar = async () => {
  console.log(edicao.value.maquineta);
  if (edicao.value.maquineta === "saurus") {
    await sSaurus.cancelarPedido();
    if (sSaurus.pedido.status == 3) {
      sSaurus.dialog.detalhesPedido = false;
    }
  } else if (edicao.value.maquineta === "pagarme") {
    await sPagarMe.cancelarPedido();
    if (sPagarMe.pedido.status == 3) {
      sPagarMe.dialog.detalhesPedido = false;
    }
  }
};

const fechar = async () => {
  sNegocio.dialog.pagamentoSaurus = false;

  sNegocio.dialog.pagamentoPagarMe = false;

  sNegocio.dialog.pagamentoCartaoManual = false;
};

const manual = async () => {
  stepManual.value = 1;

  sNegocio.dialog.pagamentoSaurus = false;

  sNegocio.dialog.pagamentoPagarMe = false;

  sNegocio.dialog.pagamentoCartaoManual = true;
};

const tiposManuais = computed(() => {
  if (!pagamento.value.codpessoa) {
    return [];
  }
  const pes = cartoesManuais.find((el) => {
    return pagamento.value.codpessoa == el.codpessoa;
  });
  return pes.tipos;
});

const bandeirasManuais = computed(() => {
  if (!pagamento.value.codpessoa) {
    return [];
  }
  const pes = cartoesManuais.find((el) => {
    return pagamento.value.codpessoa == el.codpessoa;
  });
  return pes.bandeiras;
});

const labelParceiro = computed(() => {
  if (!pagamento.value.codpessoa) {
    return [];
  }
  let ret = "";
  const pes = cartoesManuais.find((el) => {
    return pagamento.value.codpessoa == el.codpessoa;
  });
  ret += pes.apelido;
  if (tiposManuais.value.length > 1) {
    const tipo = tiposManuais.value.find((el) => {
      return pagamento.value.tipo == el.tipo;
    });
    ret += "\n" + tipo.apelido;
  }
  if (bandeirasManuais.value.length > 1) {
    const band = bandeirasManuais.value.find((el) => {
      return pagamento.value.bandeira == el.bandeira;
    });
    ret += "\n" + band.apelido;
  }
  return ret;
});

const vaiParaStepManual = async (step) => {
  switch (step) {
    case 1: // Parceiros
      break;

    case 2: // Tipo
      if (pagamento.value.codpessoa) {
        const pes = cartoesManuais.find((el) => {
          return pagamento.value.codpessoa == el.codpessoa;
        });
        // se só tem um tipo vai para próximo step
        if (pes.tipos.length == 1) {
          vaiParaStepManual(step + 1);
          return;
        }
      }
      break;

    case 4: // Bandeira
      if (pagamento.value.codpessoa) {
        const pes = cartoesManuais.find((el) => {
          return pagamento.value.codpessoa == el.codpessoa;
        });
        if (pes.bandeiras.length == 1) {
          vaiParaStepManual(step + 1);
          return;
        }
      }
      break;
  }
  stepManual.value = step;
};

const toStone = async () => {
  sNegocio.dialog.pagamentoSaurus = false;
  sNegocio.dialog.pagamentoCartaoManual = false;
  sNegocio.dialog.pagamentoPagarMe = true;
};
</script>
<template>
  <!-- DIALOG NOVO PEDIDO -->
  <q-dialog
    v-model="sNegocio.dialog.pagamentoSaurus"
    @before-show="inicializarValores()"
  >
    <q-card style="width: 600px">
      <q-form @submit="salvar()" ref="formSaurus">
        <q-card-section>
          <q-list>
            <!-- POS  -->
            <q-item>
              <q-item-section>
                <select-saurus-pos
                  outlined
                  v-model="pagamento.codsauruspos"
                  label="POS Safrapay/Saurus"
                  :codestoquelocal="sNegocio.negocio.codestoquelocal"
                  :rules="[(value) => value || 'Selecione a Maquineta!']"
                  clearable
                />
              </q-item-section>
            </q-item>

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
                  input-class="text-h2 text-weight-bolder text-right text-primary "
                />
              </q-item-section>
            </q-item>

            <!-- TIPO  -->
            <q-item>
              <q-item-section>
                <q-item-label>
                  <q-radio v-model="pagamento.tipo" :val="1" label="Débito" />
                  <q-radio v-model="pagamento.tipo" :val="2" label="Crédito" />
                  <q-radio v-model="pagamento.tipo" :val="3" label="Voucher" />
                </q-item-label>
              </q-item-section>
            </q-item>

            <!-- PARCELAS  -->
            <q-item v-if="pagamento.tipo == 2">
              <q-item-section>
                <div class="row">
                  <div
                    class="col-xs-12 col-sm-6"
                    v-for="parc in parcelamentoDisponivel"
                    :key="parc.parcelas"
                  >
                    <q-radio
                      v-model="pagamento.parcelas"
                      :val="parc.parcelas"
                      :disable="!parc.habilitado"
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
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="primary"
            @click="fechar()"
            tabindex="-1"
          />
          <q-btn
            flat
            label="Usar Stone"
            color="primary"
            @click="toStone()"
            tabindex="-1"
          />
          <q-btn
            type="button"
            flat
            label="Cartão Manual"
            @click="manual()"
            color="primary"
            tabindex="-1"
          />
          <q-btn type="submit" flat label="Enviar Maquineta" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- DIALOG CARTAO MANUAL -->

  <!-- DIALOG DETALHES PEDIDO SAURUS-->
  <q-dialog
    v-model="sSaurus.dialog.detalhesPedido"
    @show="btnConsultarRef.$el.focus()"
  >
    <q-card style="width: 600px">
      <q-card-section>
        <div class="text-h6">
          Cobrança Safrapay/Saurus de R$
          {{
            new Intl.NumberFormat("pt-BR", {
              style: "decimal",
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }).format(sSaurus.pedido.valor)
          }}
        </div>
        <div class="text-subtitle2 text-grey text-uppercase">
          {{ sSaurus.pedido.statusdescricao }}
        </div>

        <!-- PAGAMENTOS -->
        <q-list>
          <template
            v-for="pag in sSaurus.pedido.SaurusPagamentoS"
            :key="pag.codsauruspagamento"
          >
            <!-- VALOR -->
            <q-separator spaced />
            <template v-if="pag.valorcancelamento">
              <q-item>
                <q-item-section avatar>
                  <q-icon color="negative" name="attach_money" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    R$
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      }).format(pag.valorcancelamento)
                    }}
                  </q-item-label>
                  <q-item-label caption> Valor Cancelamento </q-item-label>
                </q-item-section>
              </q-item>
            </template>
            <template v-else>
              <q-item>
                <q-item-section avatar>
                  <q-icon color="secondary" name="attach_money" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    R$
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      }).format(pag.valortotal)
                    }}
                  </q-item-label>
                  <q-item-label caption> Valor efetivamente Pago </q-item-label>
                </q-item-section>
              </q-item>
            </template>

            <!-- NOME -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="person" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  {{ pag.bandeira }}
                  <span v-if="pag.nome">
                    {{ pag.nome }}
                  </span>
                </q-item-label>
                <q-item-label caption>
                  <span class="text-uppercase">
                    {{ pag.tipodescricao }}
                  </span>
                  <span v-if="pag.parcelas > 1">
                    em {{ pag.parcelas }} parcelas
                  </span>
                </q-item-label>
              </q-item-section>
            </q-item>

            <!-- ID -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="fingerprint" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="ellipsis">
                  Autorização {{ pag.autorizacao }} <br />
                </q-item-label>
                <q-item-label caption class="ellipsis">
                  NSU {{ pag.nsu }} <br />
                  Identificador {{ pag.identificador }} <br />
                  Transação {{ pag.idtransacao }} <br />
                </q-item-label>
              </q-item-section>
            </q-item>

            <!-- POS -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="point_of_sale" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  POS {{ pag.apelido }} Serial {{ pag.pos }}
                </q-item-label>
                <q-item-label caption>
                  {{ moment(pag.horario).format("LLLL") }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>

          <!-- ID PEDIDO -->
          <q-separator spaced />
          <q-item>
            <q-item-section avatar>
              <q-icon color="primary" name="post_add" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="ellipsis">
                Pedido {{ sSaurus.pedido.idpedido }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                <span v-if="sSaurus.pedido.fechado"> Fechado </span>
                <span v-else> Aberto </span>
              </q-item-label>
            </q-item-section>
          </q-item>

          <template v-if="sSaurus.pedido.SaurusPagamentoS.length == 0">
            <!-- POS -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="point_of_sale" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  POS {{ sSaurus.pedido.apelido }} Serial
                  {{ sSaurus.pedido.pos }}
                </q-item-label>
                <q-item-label caption>
                  {{ moment(sSaurus.pedido.criacao).format("LLLL") }}
                </q-item-label>
              </q-item-section>
            </q-item>

            <!-- VALOR -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="attach_money" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  R$
                  {{
                    new Intl.NumberFormat("pt-BR", {
                      style: "decimal",
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    }).format(sSaurus.pedido.valor)
                  }}
                </q-item-label>
                <q-item-label caption>
                  <span class="text-uppercase">
                    {{ sSaurus.pedido.tipodescricao }}
                  </span>
                  <span v-if="sSaurus.pedido.parcelas > 1">
                    em {{ sSaurus.pedido.parcelas }}
                    parcelas de R$
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      }).format(sSaurus.pedido.valorparcela)
                    }}
                    <span v-if="sSaurus.pedido.valorjuros"> (C/Juros) </span>
                  </span>
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>
        </q-list>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn
          flat
          label="cancelar"
          color="negative"
          @click="cancelar()"
          tabindex="-1"
          v-if="sSaurus.pedido.status != 3"
        />
        <q-btn
          flat
          label="consultar"
          color="primary"
          @click="consultar()"
          type="submit"
          ref="btnConsultarRef"
        />
        <q-btn
          flat
          label="Fechar"
          color="primary"
          @click="sSaurus.dialog.detalhesPedido = false"
          tabindex="-1"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <!-- DIALOG DETALHES PEDIDO PAGARME-->
</template>
