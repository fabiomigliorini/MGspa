<script setup>
import { ref, watch, computed } from "vue";
import { Notify } from "quasar";
import { negocioStore } from "stores/negocio";
import { pagarMeStore } from "stores/pagar-me";
import moment from "moment/min/moment-with-locales";
import SelectPagarMePos from "../selects/SelectPagarMePos.vue";
import cartoesManuais from "../../cartoes-manuais.json";

moment.locale("pt-br");

const sNegocio = negocioStore();
const sPagarMe = pagarMeStore();

const pagamento = ref({});

const parcelamentoDisponivel = ref([]);
const formPagarMe = ref(null);

const step = ref(1);

const inicializarValores = () => {
  const padrao = {
    codpagarmepos: sNegocio.padrao.codpagarmepos,
    valor: sNegocio.valorapagar,
    valorparcela: null,
    valorjuros: null,
    tipo: "1",
    parcelas: 1,
    jurosloja: true,
    codpessoa: null,
  };
  if (sNegocio.negocio.codestoquelocal != sNegocio.padrao.codestoquelocal) {
    padrao.codpagarmepos = null;
  }
  pagamento.value = padrao;
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
    pagamento.value.bandeira = null;
    return;
    if (!pagamento.value.codpessoa) {
      return;
    }
    pagamento.value.bandeira = bandeirasManuais.value[0].bandeira;
  }
);

const calcularParcelas = () => {
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

  if (tipo != 2) {
    pagamento.value.parcelas = 1;
  }
};

const calcularJuros = () => {
  var parc = parcelamentoDisponivel.value.find(
    (i) => i.parcelas == pagamento.value.parcelas
  );
  pagamento.value.valorjuros = parc.valorjuros;
  pagamento.value.valorparcela = parc.valorparcela;
};

const salvar = async () => {
  const pedido = await sNegocio.criarPagarMePedido(
    pagamento.value.codpagarmepos,
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
  sPagarMe.pedido = pedido;
  sPagarMe.dialog.detalhesPedido = true;
  sNegocio.dialog.pagamentoPagarMe = false;
};

const salvarManual = async () => {
  console.log(pagamento.value);
};

const consultar = async () => {
  await sPagarMe.consultarPedido();
  if (sPagarMe.pedido.status == 2) {
    sPagarMe.dialog.detalhesPedido = false;
  }
};

const fechar = async () => {
  sNegocio.dialog.pagamentoPagarMe = false;
  sNegocio.dialog.pagamentoCartaoManual = false;
};

const manual = async () => {
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
</script>
<template>
  <!-- DIALOG NOVO PEDIDO -->
  <q-dialog
    v-model="sNegocio.dialog.pagamentoPagarMe"
    @before-show="inicializarValores()"
  >
    <q-card>
      <q-form @submit="salvar()" ref="formPagarMe">
        <q-card-section>
          <q-list>
            <!-- POS  -->
            <q-item>
              <q-item-section>
                <select-pagar-me-pos
                  outlined
                  v-model="pagamento.codpagarmepos"
                  label="POS Stone/PagarMe"
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
  <q-dialog v-model="sNegocio.dialog.pagamentoCartaoManual">
    <q-stepper v-model="step" vertical color="primary" animated>
      <!-- PESSOA  -->
      <q-step :name="1" title="Parceiros" icon="person" :done="step > 1">
        <div class="row">
          <q-radio
            class="col-xs-12 col-sm-6 q-pa-sm"
            v-model="pagamento.codpessoa"
            v-for="pes in cartoesManuais"
            :val="pes.codpessoa"
            :key="pes.codpessoa"
          >
            <q-avatar>
              <img :src="pes.logo" />
            </q-avatar>
            {{ pes.apelido }}
          </q-radio>
        </div>
        <q-stepper-navigation>
          <q-btn @click="step = 2" color="primary" label="Continuar" />
        </q-stepper-navigation>
      </q-step>

      <!-- TIPO  -->
      <q-step :name="2" title="Tipo" icon="create_new_folder" :done="step > 2">
        <q-radio
          class="q-pa-sm"
          v-model="pagamento.tipo"
          :val="tipo.tipo"
          v-for="tipo in tiposManuais"
          :key="tipo.tipo"
          :label="tipo.apelido"
        />
        <q-stepper-navigation>
          <q-btn @click="step = 3" color="primary" label="Continuar" />
          <q-btn
            flat
            @click="step = 1"
            color="primary"
            label="Voltar"
            class="q-ml-sm"
          />
        </q-stepper-navigation>
      </q-step>

      <q-step :name="3" title="Parcelamento" icon="add_comment">
        <div class="row">
          <q-radio
            v-model="pagamento.parcelas"
            v-for="parc in parcelamentoDisponivel"
            :val="parc.parcelas"
            :key="parc.parcelas"
            class="col-xs-12 col-sm-6"
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

        <q-stepper-navigation>
          <q-btn @click="step = 4" color="primary" label="Continuar" />
          <q-btn
            flat
            @click="step = 2"
            color="primary"
            label="Voltar"
            class="q-ml-sm"
          />
        </q-stepper-navigation>
      </q-step>

      <q-step :name="4" title="Bandeira" icon="add_comment">
        <div class="row">
          <q-radio
            v-for="band in bandeirasManuais"
            :key="band.bandeira"
            v-model="pagamento.bandeira"
            :val="band.bandeira"
            :label="band.apelido"
            class="col-xs-12 col-sm-6"
          />
        </div>
        <q-stepper-navigation>
          <q-btn @click="step = 5" color="primary" label="Continuar" />
          <q-btn
            flat
            @click="step = 3"
            color="primary"
            label="Voltar"
            class="q-ml-sm"
          />
        </q-stepper-navigation>
      </q-step>

      <q-step :name="5" title="Autorização" icon="add_comment">
        <template v-if="pagamento.valorjuros">
          <!-- JUROS -->
          <q-item>
            <q-item-section class="q-pr-lg">
              <q-item-label class="text-right">
                R$
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(Math.abs(pagamento.valorjuros))
                }}
              </q-item-label>
              <q-item-label class="text-right" caption> Juros </q-item-label>
            </q-item-section>
          </q-item>

          <!-- VALOR TOTAL -->
          <q-item>
            <q-item-section class="q-pr-lg">
              <q-item-label class="text-right">
                R$
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(Math.abs(pagamento.valorjuros + pagamento.valor))
                }}
              </q-item-label>
              <q-item-label class="text-right" caption>
                Valor Total
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>

        <!-- AUTORIZACAO  -->
        <q-item>
          <q-item-section>
            <q-input
              outlined
              v-model="pagamento.autorizacao"
              :rules="[
                (value) =>
                  value.length > 6 || 'Preencha o número de Atorização!',
              ]"
              label="Autorização"
            />
          </q-item-section>
        </q-item>

        <q-stepper-navigation>
          <q-btn color="primary" @click="salvarManual()" label="Salvar" />
          <q-btn
            flat
            @click="step = 4"
            color="primary"
            label="Voltar"
            class="q-ml-sm"
          />
        </q-stepper-navigation>
      </q-step>
    </q-stepper>
  </q-dialog>

  <!-- DIALOG DETALHES PEDIDO -->
  <q-dialog v-model="sPagarMe.dialog.detalhesPedido">
    <q-card>
      <q-card-section>
        <div class="text-h6">
          Cobrança Stone/PagarMe de R$
          {{
            new Intl.NumberFormat("pt-BR", {
              style: "decimal",
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }).format(sPagarMe.pedido.valor)
          }}
        </div>
        <div class="text-subtitle2 text-grey text-uppercase">
          {{ sPagarMe.pedido.statusdescricao }}
        </div>

        <!-- PAGAMENTOS -->
        <q-list>
          <template
            v-for="pag in sPagarMe.pedido.PagarMePagamentoS"
            :key="pag.codpagarmepagamento"
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
                      }).format(pag.valorpagamento)
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
                Pedido {{ sPagarMe.pedido.idpedido }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                <span v-if="sPagarMe.pedido.fechado"> Fechado </span>
                <span v-else> Aberto </span>
              </q-item-label>
            </q-item-section>
          </q-item>

          <template v-if="sPagarMe.pedido.PagarMePagamentoS.length == 0">
            <!-- POS -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="point_of_sale" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  POS {{ sPagarMe.pedido.apelido }} Serial
                  {{ sPagarMe.pedido.pos }}
                </q-item-label>
                <q-item-label caption>
                  {{ moment(sPagarMe.pedido.criacao).format("LLLL") }}
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
                    }).format(sPagarMe.pedido.valor)
                  }}
                </q-item-label>
                <q-item-label caption>
                  <span class="text-uppercase">
                    {{ sPagarMe.pedido.tipodescricao }}
                  </span>
                  <span v-if="sPagarMe.pedido.parcelas > 1">
                    em {{ sPagarMe.pedido.parcelas }}
                    parcelas de R$
                    {{
                      new Intl.NumberFormat("pt-BR", {
                        style: "decimal",
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      }).format(sPagarMe.pedido.valorparcela)
                    }}
                    <span v-if="sPagarMe.pedido.valorjuros"> (C/Juros) </span>
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
          label="consultar"
          color="primary"
          @click="consultar()"
          tabindex="-1"
        />
        <q-btn
          flat
          label="Fechar"
          color="primary"
          @click="sPagarMe.dialog.detalhesPedido = false"
          tabindex="-1"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
