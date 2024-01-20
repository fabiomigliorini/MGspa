<script setup>
import { ref, computed } from "vue";
import { Dialog } from "quasar";
import { negocioStore } from "stores/negocio";
import { pixStore } from "stores/pix";
import { pagarMeStore } from "stores/pagar-me";
import PagamentoDinheiro from "components/offline/PagamentoDinheiro.vue";
import PagamentoPix from "components/offline/PagamentoPix.vue";
import PagamentoPagarMe from "components/offline/PagamentoPagarMe.vue";
import { formataCpf } from "../../utils/formatador.js";
import { formataCnpj } from "../../utils/formatador.js";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sNegocio = negocioStore();
const sPix = pixStore();
const sPagarMe = pagarMeStore();

const edicao = ref({
  valorprodutos: null,
  percentualdesconto: null,
  valordesconto: null,
  valorfrete: null,
  valorseguro: null,
  valoroutras: null,
  valortotal: null,
});

const editarValores = () => {
  edicao.value.valorprodutos = sNegocio.negocio.valorprodutos;
  if (sNegocio.negocio.valordesconto > 0 && sNegocio.negocio.valorprodutos) {
    edicao.value.percentualdesconto =
      Math.round(
        (sNegocio.negocio.valordesconto / sNegocio.negocio.valorprodutos) * 1000
      ) / 10;
  } else {
    edicao.value.percentualdesconto = null;
  }
  edicao.value.valordesconto = sNegocio.negocio.valordesconto;
  edicao.value.valorfrete = sNegocio.negocio.valorfrete;
  edicao.value.valorseguro = sNegocio.negocio.valorseguro;
  edicao.value.valoroutras = sNegocio.negocio.valoroutras;
  edicao.value.valortotal = sNegocio.negocio.valortotal;
  sNegocio.dialog.valores = true;
};

const maiorQueZeroRule = [
  (value) => {
    if (!value || parseFloat(value) >= 0) {
      return true;
    }
    return "Negativo!";
  },
];

const preenchimentoObrigatorioRule = [
  (val) => (val && parseFloat(val) >= 0.001) || "* Obrigatório!",
];

const salvar = async () => {
  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que você deseja salvar?",
    cancel: true,
  }).onOk(() => {
    sNegocio.aplicarValores(
      parseFloat(edicao.value.valordesconto),
      parseFloat(edicao.value.valorfrete),
      parseFloat(edicao.value.valorseguro),
      parseFloat(edicao.value.valoroutras)
    );
    sNegocio.dialog.valores = false;
  });
};

const recalcularValorDesconto = () => {
  if (edicao.value.percentualdesconto <= 0) {
    edicao.value.valordesconto = null;
  } else {
    edicao.value.valordesconto =
      Math.round(edicao.value.valorprodutos * edicao.value.percentualdesconto) /
      100;
  }
  recalcularValorTotal();
};

const recalcularPercentualDesconto = () => {
  if (edicao.value.valordesconto <= 0) {
    edicao.value.percentualdesconto = null;
  } else {
    edicao.value.percentualdesconto =
      Math.round(
        (edicao.value.valordesconto * 1000) / edicao.value.valorprodutos
      ) / 10;
  }
  recalcularValorTotal();
};

const recalcularValorTotal = () => {
  let total = parseFloat(edicao.value.valorprodutos);
  if (edicao.value.valordesconto) {
    total -= parseFloat(edicao.value.valordesconto);
  }
  if (edicao.value.valorfrete) {
    total += parseFloat(edicao.value.valorfrete);
  }
  if (edicao.value.valorseguro) {
    total += parseFloat(edicao.value.valorseguro);
  }
  if (edicao.value.valoroutras) {
    total += parseFloat(edicao.value.valoroutras);
  }
  edicao.value.valortotal = Math.round(total * 100) / 100;
};

const dialogPagamentoDinheiro = () => {
  sNegocio.dialog.pagamentoDinheiro = true;
};

const dialogPagamentoPix = () => {
  sNegocio.dialog.pagamentoPix = true;
};

const dialogPagamentoPagarMe = () => {
  sNegocio.dialog.pagamentoPagarMe = true;
};

const dialogDetalhesPixCob = (pixCob) => {
  sPix.pixCob = pixCob;
  sPix.dialog.detalhesPixCob = true;
};

const dialogDetalhesPagarMePedido = (ped) => {
  sPagarMe.pedido = ped;
  sPagarMe.dialog.detalhesPedido = true;
};

const excluirPagamento = (pag) => {
  sNegocio.excluirPagamento(pag.uuid);
};

const valorSaldoLabel = computed(() => {
  return sNegocio.valorapagar > 0 ? "Faltando" : "Troco";
});

const valorSaldoClass = computed(() => {
  return sNegocio.valorapagar > 0 ? "text-red" : "text-green";
});

const qrCodeColor = (cob) => {
  if (cob.status == "CONCLUIDA") {
    return "secondary";
  }
  return "warning";
};

const creditCardColor = (ped) => {
  // paid
  if (ped.status == 2) {
    return "secondary";
  }
  if (ped.status == 3) {
    return "grey";
  }
  return "warning";
};

const creditCardColorPagamento = (pag) => {
  // cancelamento
  if (pag.valorcancelamento) {
    return "negative";
  }
  return "secondary";
};
</script>
<template>
  <!-- Editar Valores Desconto / Frete / etc -->
  <q-dialog v-model="sNegocio.dialog.valores">
    <q-card style="width: 350px; max-width: 80vw">
      <q-form ref="formItem" @submit="salvar()">
        <q-card-section>
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6"></div>
            <div class="col-6">
              <q-input
                disable
                type="number"
                step="0.01"
                min="0.01"
                outlined
                v-model.number="edicao.valorprodutos"
                prefix="R$"
                label="Total Produtos"
                input-class="text-right"
                :rules="preenchimentoObrigatorioRule"
              />
            </div>
          </div>
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6">
              <q-input
                type="number"
                step="0.1"
                min="0"
                max="99.9"
                outlined
                v-model.number="edicao.percentualdesconto"
                label="% Desc"
                input-class="text-right"
                suffix="%"
                :rules="maiorQueZeroRule"
                @change="recalcularValorDesconto()"
                autofocus
              />
            </div>
            <div class="col-6">
              <q-input
                type="number"
                step="0.01"
                :max="edicao.valorprodutos - 0.01"
                outlined
                v-model.number="edicao.valordesconto"
                prefix="R$"
                label="Desconto"
                input-class="text-right"
                :rules="maiorQueZeroRule"
                @change="recalcularPercentualDesconto()"
              />
            </div>
          </div>
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6">
              <q-input
                type="number"
                step="0.01"
                outlined
                v-model.number="edicao.valorfrete"
                prefix="R$"
                label="Frete"
                input-class="text-right"
                :rules="maiorQueZeroRule"
                @change="recalcularValorTotal()"
              />
            </div>
          </div>
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6">
              <q-input
                type="number"
                step="0.01"
                outlined
                v-model.number="edicao.valorseguro"
                prefix="R$"
                label="Seguro"
                input-class="text-right"
                :rules="maiorQueZeroRule"
                @change="recalcularValorTotal()"
              />
            </div>
          </div>
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6">
              <q-input
                type="number"
                step="0.01"
                outlined
                v-model.number="edicao.valoroutras"
                prefix="R$"
                label="Outras"
                input-class="text-right"
                :rules="maiorQueZeroRule"
                @change="recalcularValorTotal()"
              />
            </div>
          </div>
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6">
              <q-input
                type="number"
                step="0.01"
                outlined
                v-model.number="edicao.valortotal"
                prefix="R$"
                label="Total"
                input-class="text-right"
                :rules="preenchimentoObrigatorioRule"
                @change="recalcularValorTotal()"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="primary"
            @click="sNegocio.dialog.valores = false"
            tabindex="-1"
          />
          <q-btn type="submit" flat label="Salvar" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <pagamento-dinheiro />
  <pagamento-pix />
  <pagamento-pagar-me />

  <template v-if="sNegocio.negocio">
    <q-list dense class="q-mt-md">
      <q-item
        v-if="
          parseFloat(sNegocio.negocio.valorprodutos) -
          parseFloat(sNegocio.negocio.valortotal)
        "
      >
        <q-item-section>
          <q-item-label caption>Produtos</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valorprodutos)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valordesconto">
        <q-item-section>
          <q-item-label caption>Desconto</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-weight-bolder text-green-8">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valordesconto)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valorfrete">
        <q-item-section>
          <q-item-label caption>Frete</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valorfrete)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valorseguro">
        <q-item-section>
          <q-item-label caption>Seguro</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valorseguro)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valoroutras">
        <q-item-section>
          <q-item-label caption>Outras</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valoroutras)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valorjuros">
        <q-item-section>
          <q-item-label caption>Juros</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(sNegocio.negocio.valorjuros)
            }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <!-- TOTAL -->
      <q-item
        @click="editarValores()"
        v-ripple
        :clickable="sNegocio.podeEditar"
      >
        <q-item-section class="text-right">
          <Transition
            mode="out-in"
            :duration="{ enter: 300, leave: 300 }"
            leave-active-class="animated bounceOut"
            enter-active-class="animated bounceIn"
          >
            <q-item-label class="" :key="sNegocio.negocio.valortotal">
              <span class="float-left text-grey">R$ </span>
              <span class="text-h3 text-primary text-weight-bolder">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(sNegocio.negocio.valortotal)
                }}
              </span>
            </q-item-label>
          </Transition>
        </q-item-section>
      </q-item>

      <!-- PAGAMENTOS -->
      <template v-if="sNegocio.negocio.pagamentos">
        <template v-for="pag in sNegocio.negocio.pagamentos" :key="pag.uuid">
          <q-item>
            <q-item-section>
              <q-item-label caption>
                {{ pag.nometipo }}
                <template v-if="pag.parceiro"> | {{ pag.parceiro }} </template>
              </q-item-label>
            </q-item-section>
            <q-item-section class="text-right">
              <q-item-label class="text-h5 text-grey-6">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(pag.valortotal)
                }}
                <!-- <div v-if="pag.parcelas > 1" class="text-caption">
                  {{ pag.parcelas }} Parcelas
                </div> -->
                <q-btn
                  flat
                  round
                  @click="excluirPagamento(pag)"
                  icon="delete"
                  size="sm"
                  v-if="
                    !pag.integracao && sNegocio.negocio.codnegociostatus == 1
                  "
                />
              </q-item-label>
              <q-item-label caption v-if="pag.autorizacao">
                <template v-if="pag.nomebandeira">
                  {{ pag.nomebandeira }}:
                </template>
                {{ pag.autorizacao }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>

        <!-- <q-item v-if="1 == 1"> -->
        <q-item v-if="sNegocio.valorapagar != 0">
          <q-item-section>
            <q-item-label caption>{{ valorSaldoLabel }}</q-item-label>
          </q-item-section>
          <q-item-section>
            <q-item-label :class="valorSaldoClass" class="text-right text-h5">
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(Math.abs(sNegocio.valorapagar))
              }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>
    <q-list
      class="q-pa-md q-gutter-sm text-right"
      v-if="
        sNegocio.negocio.financeiro && sNegocio.negocio.codnegociostatus == 1
      "
    >
      <!-- BOTAO DINHEIRO -->
      <q-btn
        round
        @click="dialogPagamentoDinheiro()"
        icon="local_atm"
        color="primary"
      >
        <q-tooltip class="bg-accent">Dinheiro (F6)</q-tooltip>
      </q-btn>

      <!-- BOTAO CARTAO -->
      <q-btn
        round
        icon="credit_card"
        @click="dialogPagamentoPagarMe()"
        color="primary"
      >
        <q-tooltip class="bg-accent">Cartão (F7)</q-tooltip>
      </q-btn>

      <!-- BOTAO PIX -->
      <q-btn round icon="pix" @click="dialogPagamentoPix()" color="primary">
        <q-tooltip class="bg-accent">PIX (F8)</q-tooltip>
      </q-btn>

      <!-- BOTAO Prazo -->
      <q-btn round icon="receipt" @click="dialogPrazo()" color="primary">
        <q-tooltip class="bg-accent">À Prazo (F9)</q-tooltip>
      </q-btn>
    </q-list>

    <q-list>
      <q-item
        v-for="cob in sNegocio.negocio.pixCob"
        :key="cob.codpixcob"
        clickable
        v-ripple
        @click="dialogDetalhesPixCob(cob)"
      >
        <q-item-section avatar top>
          <q-btn round :color="qrCodeColor(cob)" icon="qr_code" />
        </q-item-section>
        <q-item-section v-if="cob.status != 'CONCLUIDA'">
          <q-item-label lines="1">
            {{
              new Intl.NumberFormat("pt-BR", {
                style: "decimal",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }).format(cob.valororiginal)
            }}
          </q-item-label>
          <q-item-label caption>
            {{ cob.status }} {{ moment(cob.criacao).fromNow() }}
          </q-item-label>
        </q-item-section>
        <template v-else>
          <q-item-section v-for="pix in cob.PixS" :key="pix.codpix">
            <q-item-label lines="1">
              {{
                new Intl.NumberFormat("pt-BR", {
                  style: "decimal",
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                }).format(pix.valor)
              }}
            </q-item-label>
            <q-item-label caption>
              {{ pix.nome }}
              <br />
              <template v-if="pix.cpf">
                {{ formataCpf(pix.cpf) }}
              </template>
              <template v-if="pix.cnpj">
                {{ formataCnpj(pix.cnpj) }}
              </template>
            </q-item-label>
          </q-item-section>
        </template>
      </q-item>

      <template
        v-for="ped in sNegocio.negocio.PagarMePedidoS"
        :key="ped.codpagarmepedido"
      >
        <template v-if="ped.status != 2">
          <q-item clickable v-ripple @click="dialogDetalhesPagarMePedido(ped)">
            <q-item-section avatar top>
              <q-btn round :color="creditCardColor(ped)" icon="credit_card" />
            </q-item-section>
            <q-item-section>
              <q-item-label lines="1">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(ped.valortotal)
                }}
              </q-item-label>
              <q-item-label caption v-if="ped.parcelas > 1">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(ped.valor)
                }}
                em {{ ped.parcelas }}
                parcelas de R$
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(ped.valorparcela)
                }}
                <span v-if="ped.valorjuros"> C/Juros </span>
              </q-item-label>
              <q-item-label caption>
                <span class="text-uppercase">
                  {{ ped.tipodescricao }}
                </span>
                | POS {{ ped.apelido }} |
                <span class="text-uppercase">{{ ped.statusdescricao }}</span> |
                {{ moment(ped.criacao).fromNow() }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>
        <template v-else>
          <q-item
            clickable
            v-ripple
            @click="dialogDetalhesPagarMePedido(ped)"
            v-for="pag in ped.PagarMePagamentoS"
            :key="pag.codpagarmepagamento"
          >
            <q-item-section avatar top>
              <q-btn
                round
                :color="creditCardColorPagamento(pag)"
                icon="credit_card"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label lines="1" v-if="pag.valorpagamento">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(pag.valorpagamento)
                }}
              </q-item-label>
              <q-item-label
                lines="1"
                v-if="pag.valorcancelamento"
                class="text-negative"
              >
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(pag.valorcancelamento)
                }}
                Cancelamento
              </q-item-label>
              <q-item-label caption v-if="pag.parcelas > 1">
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(ped.valor)
                }}
                em {{ pag.parcelas }}
                parcelas de R$
                {{
                  new Intl.NumberFormat("pt-BR", {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  }).format(ped.valorparcela)
                }}
                <span v-if="ped.valorjuros"> C/Juros </span>
              </q-item-label>
              <q-item-label caption>
                {{ pag.nome }}

                <span class="text-uppercase">
                  {{ pag.bandeira }}
                  {{ pag.tipodescricao }}
                </span>
                | POS {{ pag.apelido }} |
                <span class="text-uppercase">{{ ped.statusdescricao }}</span> |
                {{ moment(pag.transacao).fromNow() }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>
      </template>
    </q-list>
  </template>
</template>
